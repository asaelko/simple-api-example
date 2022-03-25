<?php


namespace App\Domain\Core\ExperienceCenters\Service;


use App\Entity\ExperienceRequest;
use AppBundle\Service\AppConfig;
use AppBundle\Service\Mail\Mail;
use AppBundle\Service\Mail\MailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;
use Twig\Environment;

class ExperienceCenterNotificationService
{
    private AppConfig $appConfig;

    private MessageBusInterface $messageBus;

    private Environment $templatingService;

    private LoggerInterface $logger;

    private MailService $mailService;

    public function __construct(
        AppConfig $appConfig,
        MessageBusInterface $messageBus,
        Environment $templatingService,
        LoggerInterface $logger,
        MailService $mailService
    )
    {
        $this->appConfig = $appConfig;
        $this->messageBus = $messageBus;
        $this->templatingService = $templatingService;
        $this->logger = $logger;
        $this->mailService = $mailService;
    }

    public function notifyBrand(ExperienceRequest $request): void
    {
        try {
            $content = $this->templatingService->render('@Carl/emails.main/brand/new_experience_center_request.html.twig', [
                'user'     => $request->getClient(),
                'request_date' => date('m-d-Y H:i:s', $request->getScheduleSlot()->getStart())
            ]);
        } catch (Throwable $e) {
            // не удалось собрать шаблон отправляемого письма
            // залогируем критикал
            $this->logger->critical($e->getMessage());
            return;
        }

        try {
            $mail = new Mail();
            $mail
                ->setHtmlContent($content)
                ->setSubject('CARL - Запись в Experience центр')
                ->addRecipient([
                    'name' => $request->getClient()->getUsername(),
                    'email' => $request->getScheduleSlot()->getExperienceCenter()->getEmailToSendRequest(),
                ]);

            $this->mailService->sendEmail($mail);
        } catch (Throwable $e) {
            // отправка письма не удалась
            // залогируем критикал
            $this->logger->critical($e->getMessage());
        }
    }

    public function notifyClient(ExperienceRequest $request): void
    {
        try {
            $content = $this->templatingService->render('@Carl/emails.main/client/decline_experience_center_request_by_brand_manager.html.twig', []);
        } catch (Throwable $e) {
            // не удалось собрать шаблон отправляемого письма
            // залогируем критикал
            $this->logger->critical($e->getMessage());
            return;
        }

        try {
            $mail = new Mail();
            $mail
                ->setHtmlContent($content)
                ->setSubject('CARL - Запись в Experience центр')
                ->addRecipient([
                    'name' => $request->getClient()->getUsername(),
                    'email' => $request->getClient()->getEmail(),
                ]);

            $this->mailService->sendEmail($mail);
        } catch (Throwable $e) {
            // отправка письма не удалась
            // залогируем критикал
            $this->logger->critical($e->getMessage());
        }
    }
}
