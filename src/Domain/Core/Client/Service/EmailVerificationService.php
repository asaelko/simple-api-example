<?php

namespace App\Domain\Core\Client\Service;

use App\Domain\Core\Client\Controller\Request\SendEmailVerificationRequest;
use App\Domain\Core\Client\Repository\EmailVerificationRepository;
use App\Entity\Client\EmailVerification;
use AppBundle\Service\AppConfig;
use AppBundle\Service\Mail\Mail;
use AppBundle\Service\Mail\MailService;
use CarlBundle\Entity\Client;
use CarlBundle\Exception\EarlyForResentException;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Exception\RestException;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Сервис подтверждения email-ов пользователей
 */
class EmailVerificationService
{
    private Security $security;
    private EmailVerificationRepository $emailVerificationRepository;
    private EntityManagerInterface $entityManager;
    private MailService $mailService;
    private TwigEnvironment $twigEngine;
    private AppConfig $appConfig;

    public function __construct(
        Security $security,
        EmailVerificationRepository $emailVerificationRepository,
        EntityManagerInterface $entityManager,
        MailService $mailService,
        TwigEnvironment $twigEngine,
        AppConfig $appConfig
    )
    {
        $this->security = $security;
        $this->emailVerificationRepository = $emailVerificationRepository;
        $this->entityManager = $entityManager;
        $this->mailService = $mailService;
        $this->twigEngine = $twigEngine;
        $this->appConfig = $appConfig;
    }

    /**
     * Метод запроса проверки указанного почтового адреса клиента
     *
     * @param SendEmailVerificationRequest $emailVerificationRequest
     * @param Client|null $client
     * @throws EarlyForResentException
     * @throws InvalidValueException
     * @throws RestException
     */
    public function processVerificationRequest(SendEmailVerificationRequest $emailVerificationRequest, ?Client $client = null): void
    {
        $client ??= $this->security->getUser();
        assert($client instanceof Client);
//
//        if ($emailVerificationRequest->email === $client->getEmail() && $client->isEmailVerified()) {
//            // этот email уже подтвержден
//            return;
//        }

        $anotherClients = $this->entityManager->getRepository(Client::class)->checkClientUniqueness([
            'id' => $client->getId(),
            'email' => $emailVerificationRequest->email,
            'appTag' => $client->getAppTag()
        ]);

        if ($anotherClients) {
            throw new InvalidValueException('error.email_verification.already_used');
        }

        // проверяем, что там у клиента с тест-драйвами
        $drives = $client->getUnfinishedDrives();
        if ($drives->count() > 0) {
            throw new InvalidValueException('error.email_verification.test_drive_locked');
        }

        // проверяем, когда он в последний раз запрашивал подтверждение email-а
        $verificationRequest = $this->emailVerificationRepository->getLastVerificationRequest($client);
        if ($verificationRequest) {
            // у пользователя был запрос на подтверждение

            // если в запросе на подтверждение почты тот же email, что запрашивается сейчас – проверяем, был ли он больше минуты назад
            $resendIn = $verificationRequest->getCreatedAt()->getTimestamp() + EmailVerification::RESEND_TIME_SEC - (new DateTime)->getTimestamp();
            if ($resendIn > 0) {
                $ex = new EarlyForResentException('error.email_verification.too_early_for_resend');
                $ex->setData(['resend_time' => $resendIn]);
                throw $ex;
            }

            // если в запросе на подтверждение указан email, отличающийся от текущего пользовательского,
            // проверяем, был ли он сменен менее часа назад
            $resendIn = $verificationRequest->getExpirationAt()->getTimestamp() - (new DateTime)->getTimestamp();
            if ($resendIn > 0 && $emailVerificationRequest->email !== $verificationRequest->getEmail() && $verificationRequest->getValidatedAt()) {
                $ex = new EarlyForResentException('error.email_verification.too_early_for_change');
                $ex->setData(['resend_time' => $resendIn]);
                throw $ex;
            }
        }

        $verificationToken = md5(uniqid('', true));
        $verificationRequest = new EmailVerification();
        $verificationRequest->setClient($client)
            ->setEmail($emailVerificationRequest->email)
            ->setVerificationToken($verificationToken);

        $client->setEmail($verificationRequest->getEmail())
            ->setEmailVerificationRequestAt(DateTime::createFromImmutable($verificationRequest->getCreatedAt()))
            ->setEmailVerified(false)
            ->setVerificationEmailToken($verificationRequest->getVerificationToken());

        $this->entityManager->persist($verificationRequest);
        $this->entityManager->flush();

        try {
            $this->sendVerificationEmail($verificationRequest);
        } catch (Exception $e) {
            throw new RestException('error.email_verification.service_problem');
        }
    }

    /**
     * Метод валидации почты пользователя по токену, полученному им в письме
     *
     * @param string $verificationToken
     * @return EmailVerification
     * @throws InvalidValueException
     */
    public function validateEmailByToken(string $verificationToken): EmailVerification
    {
        if (!$this->appConfig->isProd()) {
            if ($verificationToken === 'missing') {
                throw new NotFoundHttpException('error.email_verification.missing_request');
            }
            if ($verificationToken === 'expired') {
                throw new NotFoundHttpException('error.email_verification.request_expired');
            }
            if ($verificationToken === 'valid') {
                /** @var Client $client */
                $client = $this->entityManager->getRepository(Client::class)->findOneBy(['deletedAt' => null]);
                return (new EmailVerification())->setClient($client);
            }
        }

        $verificationRequest = $this->emailVerificationRepository->getPendingRequestByToken($verificationToken);

        if (!$verificationRequest) {
            throw new NotFoundHttpException('error.email_verification.missing_request');
        }

        if ($verificationRequest->getExpirationAt()->getTimestamp() < (new DateTime)->getTimestamp()) {
            throw new NotFoundHttpException('error.email_verification.request_expired');
        }

        $client = $verificationRequest->getClient();
        // не проверяем, что там у клиента с тест-драйвами
//        $drives = $client->getUnfinishedDrives();
//        if ($drives->count() > 0) {
//            throw new InvalidValueException('error.email_verification.test_drive_locked');
//        }

        $verificationRequest->setValidatedAt(new DateTimeImmutable());
        $client->setEmailVerified(true);
        $client->setVerifiedAt(DateTime::createFromImmutable($verificationRequest->getValidatedAt()));

        $this->entityManager->persist($verificationRequest);
        $this->entityManager->flush();

        return $verificationRequest;
    }

    /**
     * Отправляет письмо с токеном подтверждения клиенту
     *
     * @param EmailVerification $emailVerification
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function sendVerificationEmail(EmailVerification $emailVerification): void
    {
        $client = $emailVerification->getClient();
        $wlPostfix = $client->getAppTag() ?? 'main';
        $templatePath = "@Carl/emails.{$wlPostfix}/client/email_verification.html.twig";
        $htmlContent = $this->twigEngine->render($templatePath, [
            'client' => $client,
        ]);

        $subject = 'Пожалуйста, подтвердите свой e-mail адрес';

        $Mail = new Mail();
        $Mail
            ->setHtmlContent($htmlContent)
            ->setSubject($subject)
            ->addRecipient([
                'name' => $client->getFullName(),
                'email' => $client->getEmail(),
            ]);

        $sender = $this->appConfig->getWlConfig($client->getAppTag())['mail']['sender'];
        $Mail->setSenderName($sender['name'])
            ->setSenderEmail($sender['mail']);

        $this->mailService->sendEmail($Mail);
    }
}
