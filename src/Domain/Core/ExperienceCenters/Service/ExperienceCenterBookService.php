<?php


namespace App\Domain\Core\ExperienceCenters\Service;


use App\Domain\Core\ExperienceCenters\Request\ClientBookRequest;
use App\Entity\ExperienceCenterSchedule;
use App\Entity\ExperienceRequest;
use CarlBundle\Entity\Client;
use CarlBundle\Exception\Payment\UnknownPaymentException;
use CarlBundle\Exception\RestException;
use CarlBundle\Service\Payment\PaymentService;
use CarlBundle\Service\Payment\PaymentServiceFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ExperienceCenterBookService
{
    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    private ExperienceCenterNotificationService $notificationService;

    private PaymentService $paymentService;

    private ExperienceCenterPaymentService $experienceCenterPaymentService;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ExperienceCenterNotificationService $notificationService,
        PaymentServiceFactory $paymentServiceFactory,
        ExperienceCenterPaymentService $experienceCenterPaymentService
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->notificationService = $notificationService;
        $this->paymentService = $paymentServiceFactory->getPaymentService(PaymentServiceFactory::TINKOFF_WEB_SERVICE);
        $this->experienceCenterPaymentService = $experienceCenterPaymentService;
    }

    /**
     * @param ClientBookRequest $request
     * @param Client $client
     * @return bool
     * @throws RestException
     * @throws UnknownPaymentException
     */
    public function bookSlot(ClientBookRequest $request, Client $client): bool
    {
        /** @var ExperienceCenterSchedule $slot */
        $slot = $this->entityManager->getRepository(ExperienceCenterSchedule::class)->find($request->slotId);

        //Если поездка бесплатная
        if ($slot->getPrice() == 0) {
            $slotBook = $this->createRequest($client, $slot);

            $slot->setIsBooked(true);
            $this->entityManager->persist($slot);

            $this->entityManager->persist($slotBook);
            $this->entityManager->flush();
            $this->notificationService->notifyBrand($slotBook);
            return true;
        }

        //Если оплачена на стороне мобильного приложения
        if ($request->paymentId) {
            $state = $this->paymentService->checkPayment($request->paymentId);

            if (!$state->isSuccessful()) {
                return false;
            }

            $slotBook = $this->createRequest($client, $slot, $request->paymentId);

            $slot->setIsBooked(true);
            $this->entityManager->persist($slot);

            $this->entityManager->persist($slotBook);
            $this->entityManager->flush();
            $this->notificationService->notifyBrand($slotBook);
            return true;
        }

        //Если платная но надо провести серверный платеж
        if ($slot->getPrice() === 0) {
            return false;
        }
        $slotBook = $this->createRequest($client, $slot);
        $transactionId = $this->experienceCenterPaymentService->initPayment($client, $slotBook);
        $slotBook->setPaymentId($transactionId);

        $slot->setIsBooked(true);
        $this->entityManager->persist($slot);

        $this->entityManager->persist($slotBook);
        $this->entityManager->flush();
        $this->notificationService->notifyBrand($slotBook);
        return true;
    }

    /**
     * Создание сущности ExperienceRequest
     * @param Client $client
     * @param ExperienceCenterSchedule $slot
     * @param string|null $paymentId
     * @return ExperienceRequest
     */
    public function createRequest(Client $client, ExperienceCenterSchedule $slot, ?string $paymentId = null): ExperienceRequest
    {
        $slotBook = new ExperienceRequest();
        $slotBook->setClient($client);
        $slotBook->setPaymentStatus(ExperienceRequest::PAYMENT_STATUS_CONFIRMED);
        $slotBook->setState(ExperienceRequest::STATE_APPROVE);
        $slotBook->setScheduleSlot($slot);
        $slotBook->setPaymentId($paymentId);

        return $slotBook;
    }

    /**
     * @param ExperienceRequest $request
     * @return bool
     * @throws RestException
     * @throws UnknownPaymentException
     */
    public function declineRequestByBrand(ExperienceRequest $request): bool
    {
        $request->getScheduleSlot()->setIsBooked(false);
        $this->entityManager->persist($request->getScheduleSlot());

        if (!$request->getPaymentId()) {
            $request->setState(ExperienceRequest::STATE_DECLINE);
            $this->notificationService->notifyClient($request);

            $this->entityManager->persist($request);
            $this->entityManager->flush();
            return true;
        }

        if ($request->getPaymentId() && $request->getPaymentStatus() == ExperienceRequest::PAYMENT_STATUS_CONFIRMED) {
            $status = $this->paymentService->cancelPayment($request->getPaymentId());
            if ($status->isSuccessful()) {
                $request->setState(ExperienceRequest::STATE_DECLINE);
                $this->notificationService->notifyClient($request);

                $this->entityManager->persist($request);
                $this->entityManager->flush();
                return true;
            } else {
                throw new RestException('Error decline payment', 432);
            }
        }

        return true;
    }
}