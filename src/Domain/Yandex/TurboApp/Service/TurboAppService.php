<?php

namespace App\Domain\Yandex\TurboApp\Service;

use App\Domain\Core\Client\Service\ClientAuthService;
use App\Domain\Yandex\TurboApp\ApiClient;
use App\Domain\Yandex\TurboApp\Controller\Request\BookingRequest;
use App\Domain\Yandex\TurboApp\Exception\NoAuthDataException;
use CarlBundle\Entity\Car;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use CarlBundle\Entity\Schedule;
use CarlBundle\Exception\ClientIsBanLoginException;
use CarlBundle\Exception\ExistsActiveDriveException;
use CarlBundle\Exception\RestException;
use CarlBundle\Request\Client\ClientRegistrationRequest;
use CarlBundle\Service\Client\RegistrationService;
use CarlBundle\Service\Drive\DriveBooker;
use CarlBundle\Service\Drive\DTO\CreateDriveFromAppRequest;
use CarlBundle\Service\Drive\DTO\Location;
use CarlBundle\Service\Drive\Exception\ServerPaymentRequiredException;
use CarlBundle\Service\Payment\PaymentService;
use CarlBundle\Service\Prebooking\PrebookingService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TurboAppService
{
    private ApiClient $yandexApiClient;
    private LoggerInterface $logger;
    private RegistrationService $registrationService;
    private ClientAuthService $authService;
    private EntityManagerInterface $entityManager;
    private KassaService $kassaService;
    private PrebookingService $prebookingService;
    private DriveBooker $driveBooker;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        ApiClient $yandexApiClient,
        ClientAuthService $authService,
        LoggerInterface $yandexLogger,
        RegistrationService $registrationService,
        EntityManagerInterface $entityManager,
        KassaService $kassaService,
        PrebookingService $prebookingService,
        DriveBooker $driveBooker,
        ParameterBagInterface $parameterBag
    )
    {
        $this->yandexApiClient = $yandexApiClient;
        $this->logger = $yandexLogger;
        $this->registrationService = $registrationService;
        $this->authService = $authService;
        $this->entityManager = $entityManager;
        $this->kassaService = $kassaService;
        $this->prebookingService = $prebookingService;
        $this->driveBooker = $driveBooker;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Авторизуем клиента по яндекс-токену
     *
     * @param string $token
     * @return Client
     * @throws ClientIsBanLoginException
     * @throws NoAuthDataException
     */
    public function authUserByYandexToken(string $token): Client
    {
        $data = $this->yandexApiClient->requestAuthData($token);
        if (!$data) {
            throw new NoAuthDataException('Не получили авторизационные данные от Яндекса');
        }

        $client = $this->authService->tryAuthBy(
            ['yandexPsuid' => $data['psuid']],
        );

        // если не удалось найти клиента по Yandex ID, ищем по почте
        if (!$client) {
            $client = $this->authService->tryAuthBy(
                ['email' => $data['default_email']],
            );
        }

        if (!$client) {
            $registrationRequest = new ClientRegistrationRequest();
            $registrationRequest->firstName = $data['first_name'];
            $registrationRequest->secondName = $data['last_name'];
            $registrationRequest->email = $data['default_email'];
            $registrationRequest->psuid = $data['psuid'];

            try {
                $registrationRequest->birthDate = (new DateTime($data['birthday']))->getTimestamp();
            } catch (Exception $e) {
                $this->logger->warning($e, $data);
            }

            $client = $this->registrationService->createNewClientWithoutSendSms($registrationRequest);
        }

        $client->setEmailVerified(true);
        $client->setYandexPsuid($data['psuid']);
        $this->entityManager->flush();

        return $client;
    }

    /**
     * Бронирует ТД по запросу из Я.ТурбоАппы
     *
     * @param BookingRequest $request
     * @param Client         $client
     *
     * @return Drive
     * @throws RestException
     * @throws ExistsActiveDriveException
     * @throws ServerPaymentRequiredException
     */
    public function bookDrive(BookingRequest $request, Client $client): Drive
    {
        $car = $this->entityManager->getRepository(Car::class)->get($request->car);
        $start = DateTime::createFromFormat('U', $request->slot);

        try {
            $schedule = $this->entityManager->getRepository(Schedule::class)->getScheduleForCarByTime($car, $start);
            if (!$schedule || $schedule->getClosedAt()) {
                throw new RestException('Выбранное время недоступно', 409);
            }

            $createDriveFromAppRequest = new CreateDriveFromAppRequest(
                $schedule,
                $start,
                $client,
                new Location($request->location['lat'], $request->location['lng'], $request->location['name'] ?? null),
            );

            $drive = $this->driveBooker->createDriveFromAppRequest($createDriveFromAppRequest);

            if ($drive->getDriveRate()->isFree()) {
                $drive->setState(Drive::STATE_NEW);
                $drive->setPaymentId(-1);
                $drive->setPaymentStatus(PaymentService::STATUS_CONFIRMED);

                $this->entityManager->persist($drive);
                $this->entityManager->flush();
                return $drive;
            }

            $session = $this->prebookingService->createSessionFromDrive($drive);
            $session->setExpiresAt((new \DateTimeImmutable())->add(new \DateInterval('PT20M')));
            $this->entityManager->persist($session);
            $this->entityManager->flush();

            $payment = $this->kassaService->createYandexKassaPayment($drive, $session);
            if (isset($payment['status'], $payment['data']['pay_token']) && $payment['status'] === 'success') {
                $paymentEntity = $this->kassaService->createPaymentEntity($payment, $session);
                $this->entityManager->persist($paymentEntity);
                $this->entityManager->flush();

                throw (new ServerPaymentRequiredException('Необходимо оплатить тест-драйв', 402))
                    ->setClientId($drive->getClient()->getId())
                    ->setRedirectUrl($payment['data']['pay_token'])
                    ->setTransactionId(null)
                    ->setSessionId($session->getCode()->toString());
            } else {
                $this->entityManager->remove($session);
                $this->entityManager->flush();
                throw new RestException('Не удалось инициировать оплату');
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * Обрабатываем событие платежа от Яндекса
     *
     * @param string $jsonPayment
     */
    public function processPayment(string $jsonPayment): void
    {
        try {
            $data = json_decode($jsonPayment, true, 512, JSON_THROW_ON_ERROR);
            $message = $data['message'] ?? null;

            if (!$message) {
                throw new Exception('No message in token ' . $jsonPayment);
            }

            $jwk = new JWK([
                'kty' => 'oct',
                'k' => $this->parameterBag->get('yandex')['kassa']['jwt_key'],
            ]);

            $serializerManager = new JWSSerializerManager([
                new CompactSerializer(),
            ]);

            $jws = $serializerManager->unserialize($message);

            $algorithmManager = new AlgorithmManager([new HS256()]);
            $jwsVerifier = new JWSVerifier($algorithmManager);
            $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);
            if (!$isVerified) {
                throw new Exception('Cannot verify notification JWT signature: ' . $jsonPayment);
            }

            $paymentData = json_decode($jws->getPayload(), true, 512, JSON_THROW_ON_ERROR);

            $this->logger->info(
                sprintf('Received update on session %s: new status "%s"', $paymentData['meta'], $paymentData['new_status'])
            );

            if ($paymentData['new_status'] === 'paid') {
                $session = $this->prebookingService->getSession($paymentData['meta']);
                if (!$session) {
                    $this->kassaService->returnPayment($paymentData);
                    return;
                }

                $drive = $this->driveBooker->bookFromPrebookingSession($session, null);
                $this->entityManager->persist($drive);

                $session->setPaymentStatus(PaymentService::STATUS_CONFIRMED);
                $session->setPaymentUpdateDate(new DateTime());
                $session->setDrive($drive);
                $this->entityManager->flush();
            }
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }
}
