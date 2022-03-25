<?php

namespace App\Domain\Core\Client\Service;

use App\Domain\Core\Client\Exception\ExpiredVerificationRequestApiException;
use App\Domain\Core\Client\Exception\InvalidVerificationCodeApiException;
use App\Domain\Core\Client\Exception\MissingVerificationRequestApiException;
use App\Domain\Core\Client\Exception\TooEarlyForCodeResendApiException;
use AppBundle\Helper\PasswordGenerator;
use AppBundle\Service\AppConfig;
use AppBundle\Service\Phone\PhoneService;
use CarlBundle\Entity\Phone\PhoneVerification;
use CarlBundle\Entity\User;
use CarlBundle\Exception\CantSendSMSException;
use CarlBundle\Repository\Phone\PhoneVerificationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Сервис, отвечающий за подтверждение номеров телефонов
 */
class PhoneVerificationService
{
    private const CODE_LENGTH = 4;
    private const KEYSPACE = '1234567890';

    private string $hashingSalt = 'aae9d8aa-c965-4e37-8112-6312e917f9ee';

    private EntityManagerInterface $entityManager;
    private PhoneVerificationRepository $verificationRepository;
    private PasswordEncoderInterface $passwordEncoder;
    private PhoneService $phoneService;
    private AppConfig $appConfig;

    public function __construct(
        EntityManagerInterface $entityManager,
        PhoneVerificationRepository $verificationRepository,
        EncoderFactoryInterface $passwordEncoderFactory,
        PhoneService $phoneService,
        AppConfig $appConfig
    )
    {
        $this->verificationRepository = $verificationRepository;
        $this->passwordEncoder = $passwordEncoderFactory->getEncoder(User::class);
        $this->entityManager = $entityManager;
        $this->phoneService = $phoneService;
        $this->appConfig = $appConfig;
    }

    /**
     * Отправляет код подтверждения по указанному номеру телефона и создает новую сессию подтверждения номера
     *
     * @param string $phone
     * @param int $length
     * @return PhoneVerification
     *
     * @throws CantSendSMSException
     * @throws TooEarlyForCodeResendApiException
     */
    public function sendVerificationCode(string $phone, int $length = self::CODE_LENGTH): PhoneVerification
    {
        // проверяем, нет ли уже существующего запроса на подтверждение этого номера
        // запроса без подтверждения и с неистекшим сроком действия
        // если есть и время возможности повторного запроса еще не наступило, шлем 425 Too Early
        $lastRequest = $this->verificationRepository->getPendingRequestsForNumber($phone);

        if ($lastRequest && ($timeBeforeResend = $lastRequest->timeBeforeResendAllowed()) > 0) {
            $ex = new TooEarlyForCodeResendApiException();
            $ex->setData(['resend_time' => $timeBeforeResend]);

            throw $ex;
        }

        // если можно выслать новый запрос – создаем новую сущность и отправляем запрос
        $verificationRequest = new PhoneVerification($phone);

        $this->sendCode($verificationRequest, $length);

        $this->entityManager->persist($verificationRequest);
        $this->entityManager->flush();

        return $verificationRequest;
    }

    /**
     * Проверяем отправленный код подтверждения
     *
     * @param string $verificationId
     * @param string $code
     * @return PhoneVerification
     *
     * @throws CantSendSMSException
     * @throws ExpiredVerificationRequestApiException
     * @throws InvalidVerificationCodeApiException
     * @throws MissingVerificationRequestApiException
     */
    public function checkVerificationCode(string $verificationId, string $code): PhoneVerification
    {
        $verificationRequest = $this->verificationRepository->find($verificationId);

        // если такой сессии нет - возвращаем 404
        if (!$verificationRequest) {
            throw new MissingVerificationRequestApiException();
        }

        assert($verificationRequest instanceof PhoneVerification);

        // если сессия истекла - возвращаем 410
        if ($verificationRequest->isExpired()) {
            // и отправляем новый код подтверждения
            $this->sendCode($verificationRequest, $verificationRequest->getCodeLength());
            $this->entityManager->flush();

            throw new ExpiredVerificationRequestApiException();
        }

        if ($this->passwordEncoder->isPasswordValid($verificationRequest->getVerificationCode(), $code, $this->hashingSalt)) {
            $verificationRequest->setValidatedAt(new DateTimeImmutable());
            $this->entityManager->flush();

            return $verificationRequest;
        }

        throw new InvalidVerificationCodeApiException();
    }

    /**
     * Генерируем и отправляем код подтверждения пользователю
     *
     * @param PhoneVerification $phoneVerification
     * @param int $length
     * @throws CantSendSMSException
     */
    private function sendCode(PhoneVerification $phoneVerification, int $length = self::CODE_LENGTH): void
    {
        $verificationCode = PasswordGenerator::getPassword($length, self::KEYSPACE);

        // для тестового окружения принимаем любые телефоны, начинающиеся на 7900
        $isTestPhone = false;
        if (!$this->appConfig->isProd() && strncmp($phoneVerification->getPhoneNumber(), '7900', 4) === 0) {
            $isTestPhone = true;
            $verificationCode = substr($phoneVerification->getPhoneNumber(), -1 * $length);
        } elseif ($this->appConfig->isProd() && strcmp($phoneVerification->getPhoneNumber(), '79001234567') === 0) {
            $isTestPhone = true;
            $verificationCode = substr(self::KEYSPACE, 0, $length);
        }

        $encodedCode = $this->passwordEncoder->encodePassword($verificationCode, $this->hashingSalt);

        $now = new DateTimeImmutable();
        $phoneVerification
            ->setCreatedAt($now)
            ->setExpirationAt((new DateTimeImmutable())->setTimestamp($now->getTimestamp() + PhoneVerification::EXPIRATION_TIME_SEC))
            ->setVerificationCode($encodedCode)
            ->setCodeLength($length);

        if (!$isTestPhone) {
            $this->phoneService->sendSms(
                $phoneVerification->getPhoneNumber(),
                sprintf('%s - ваш код подтверждения', $verificationCode)
            );
        }
    }
}
