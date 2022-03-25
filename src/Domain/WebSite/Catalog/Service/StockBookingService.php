<?php

namespace App\Domain\WebSite\Catalog\Service;

use App\Domain\Core\Client\Service\ClientAuthService;
use App\Domain\WebSite\Catalog\Request\AnonymousBookingRequest;
use App\Entity\BookingRequest\BookingRequest;
use CarlBundle\Entity\Phone\PhoneVerification;
use CarlBundle\Exception\Payment\MaxBookingCountException;
use CarlBundle\Repository\Phone\PhoneVerificationRepository;
use CarlBundle\Request\Client\ClientRegistrationRequest;
use CarlBundle\Service\Client\ClientPaymentDataManager;
use CarlBundle\Service\Client\RegistrationService;
use DateTime;
use DealerBundle\Entity\CallbackAction;
use DealerBundle\Entity\Car;
use DealerBundle\Entity\DriveOffer;
use DealerBundle\Service\BookingPaymentService;
use DealerBundle\ServiceRepository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StockBookingService
{
    private EntityManagerInterface $entityManager;
    private StockRepository $stockRepository;
    private PhoneVerificationRepository $phoneVerificationRepository;
    private RegistrationService $registrationService;
    private ClientAuthService $authService;
    private ClientPaymentDataManager $clientPaymentDataManager;
    private BookingPaymentService $bookingPaymentService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        StockRepository $stockRepository,
        PhoneVerificationRepository $phoneVerificationRepository,
        ClientAuthService $authService,
        RegistrationService $registrationService,
        ClientPaymentDataManager $clientPaymentDataManager,
        BookingPaymentService $bookingPaymentService,
        LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->stockRepository = $stockRepository;
        $this->phoneVerificationRepository = $phoneVerificationRepository;
        $this->registrationService = $registrationService;
        $this->authService = $authService;
        $this->clientPaymentDataManager = $clientPaymentDataManager;
        $this->bookingPaymentService = $bookingPaymentService;
        $this->logger = $logger;
    }

    /**
     * Бронируем авто из стока
     *
     * @param int $stockId
     *
     * @return array
     * @throws \JsonException
     */
    public function book(AnonymousBookingRequest $request, int $stockId): array
    {
        /** @var Car $car */
        $car = $this->stockRepository->find($stockId);

        if (!$car) {
            throw new NotFoundHttpException('Машина не найдена');
        }

        if (!$car->getDealer()->hasBookingAbility()) {
            throw new AccessDeniedHttpException('У дилера нет разрешения на бронирование');
        }

        if ($car->getState() === Car::STATE_SOLD) {
            throw new AccessDeniedHttpException('Машина уже была забронирована');
        }

        // создаем клиента из переданных данных
        /** @var PhoneVerification $phoneVerificationRequest */
        $phoneVerificationRequest = $this->phoneVerificationRepository->find($request->phoneToken);
        $phone = $phoneVerificationRequest->getPhoneNumber();

        $client = $this->authService->tryAuthBy([
            'phone' => $phone
        ]);

        if (!$client) {
            $clientRegRequest = new ClientRegistrationRequest();
            $clientRegRequest->firstName = $request->name;
            $clientRegRequest->secondName = $request->lastName;
            $clientRegRequest->phone = $phone;

            $client = $this->registrationService->createNewClientWithoutSendSms($clientRegRequest);
        } else {
            /** @var array $clientOffers */
            $clientOffers = $this->entityManager->getRepository(DriveOffer::class)->getClientBookings($client);
            if (count($clientOffers) >= 3) {
                throw new MaxBookingCountException();
            }
        }

        if (!$client->getPaymentCustomerKey()) {
            $this->clientPaymentDataManager->addPaymentCustomerKey($client);
        }

        $bookingRequest = new BookingRequest();
        $bookingRequest->setDealerCar($car)
            ->setClient($client);

        $PaymentTransaction = $this->bookingPaymentService->bookWebStock($bookingRequest);

        $bookingRequest->setTransactionId($PaymentTransaction->getId())
            ->setTransactionStatus($PaymentTransaction->getStatus())
            ->setTransactionTime(new DateTime());

        // remove another offers
        try {
            $this->entityManager->getFilters()->disable('role_aware_filter');

            $this->entityManager->getRepository(DriveOffer::class)
                ->deleteOffersForOtherClientsOnBooking($bookingRequest->getDealerCar(), $bookingRequest->getClient());

            $this->entityManager->getRepository(CallbackAction::class)
                ->deleteCallbacksForOtherClientsOnBooking($bookingRequest->getDealerCar(), $bookingRequest->getClient());

            $this->entityManager->getFilters()->enable('role_aware_filter');
        } catch (Exception $Ex) {
            $this->logger->error(sprintf(
                'Cannot delete offers and callbacks on booking, car: %d , client: %d',
                $bookingRequest->getDealerCar(),
                $bookingRequest->getClient()
            ));
        }

        $this->entityManager->persist($bookingRequest);
        $this->entityManager->flush();

//        $this->offerNotificationService->sendPrepaymentEmailToDealer($Offer);
//        $this->slackNotificatorService->sendNewDriveOfferBookedMessage($Offer);

        return [
            'result' => true,
            'redirectUrl' => $PaymentTransaction->getPaymentURL(),
            'transactionId' => $PaymentTransaction->getId()
        ];
    }
}
