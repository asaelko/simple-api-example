<?php


namespace App\Domain\Core\ExperienceCenters\Service;


use App\Entity\ExperienceRequest;
use CarlBundle\Entity\Client;
use CarlBundle\Exception\Payment\CantHoldMoneyException;
use CarlBundle\Exception\Payment\NoPaymentDataException;
use CarlBundle\Exception\Payment\UnknownPaymentException;
use CarlBundle\Service\Payment\PaymentService;
use CarlBundle\Service\Payment\PaymentServiceFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ExperienceCenterPaymentService
{
    private PaymentService $paymentService;

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    public function __construct(
        PaymentServiceFactory $paymentServiceFactory,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    )
    {
        $this->paymentService = $paymentServiceFactory->getPaymentService(PaymentServiceFactory::TINKOFF_WEB_SERVICE);
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Client $client
     * @param ExperienceRequest $experienceRequest
     * @return string|null
     * @throws CantHoldMoneyException
     * @throws NoPaymentDataException
     * @throws UnknownPaymentException
     */
    public function initPayment(Client $client, ExperienceRequest $experienceRequest): ?string
    {
        if (!$client->getPaymentCustomerKey() || !$client->getPaymentCardId()) {
            throw new NoPaymentDataException('Customer key or payment data not found');
        }

        $id = time();
        $orderId     = "{$id}-{$experienceRequest->getScheduleSlot()->getExperienceCenter()->getId()}";
        $amount      = floor($experienceRequest->getScheduleSlot()->getPrice() * 100);
        $description = sprintf(
            'Оплата записи в %s',
            $experienceRequest->getScheduleSlot()->getExperienceCenter()->getName()
        );

        $payment = $this->paymentService->generateNewPayment();
        $payment->setOrderId($orderId)
            ->setAmount($amount)
            ->setDescription($description)
            ->setCustomData([
                'Email' => $client->getEmail(),
            ])
            ->setReceipt($this->generateReceipt($client, $experienceRequest->getScheduleSlot()->getPrice()))
            ->setCustomerKey($client->getPaymentCustomerKey());

        $paymentTransaction = $this->paymentService->initPayment($payment);

        if (!($paymentTransaction->isSuccessful() && $paymentTransaction->getId())) {
            $logMessage = sprintf(
                '[Drive] Cannot init payment on card due to: %s %s, payment data: %s',
                $paymentTransaction->getErrorMessage(),
                $paymentTransaction->getErrorDetails(),
                json_encode($payment->exportData())
            );
            $this->logger->critical($logMessage);

            throw new CantHoldMoneyException(
                'Payment service response: ' . $paymentTransaction->getErrorMessage()
            );
        }

        $holdTransaction = $this->paymentService->holdPayment(
            $paymentTransaction->getId(),
            $client->getPaymentCardId()
        );
        if (!$holdTransaction->isSuccessful()) {
            $logMessage = sprintf(
                '[Drive] Cannot hold payment on card due to: %s %s, payment data: %s',
                $holdTransaction->getErrorMessage(),
                $holdTransaction->getErrorDetails(),
                json_encode($payment->exportData())
            );
            $this->logger->critical($logMessage);

            throw new CantHoldMoneyException(
                'Payment service response: ' . $holdTransaction->getErrorMessage()
            );
        }

        try {
            $transaction = $this->paymentService->confirmPayment(
                $holdTransaction->getId(),
                null,
                null
            );
        } catch (UnknownPaymentException $exception) {
            $this->logger->critical(
                sprintf(
                    '[Purchase] Cannot confirm payment on Offer #%d in status %s: %s',
                    $experienceRequest->getId(),
                    $paymentTransaction->getStatus(),
                    $exception->getMessage()
                )
            );
            throw $exception;
        }

        if (!$transaction->isSuccessful()) {
            throw new UnknownPaymentException(
                sprintf('[Purchase] %s: %s', $transaction->getErrorCode(), $transaction->getErrorMessage())
            );
        }

        return $transaction->getId();
    }

    /**
     * Генерируем чек для Клиента/Тинькова
     *
     * @param Client $client
     * @param int $price
     * @return array
     */
    public function generateReceipt(Client $client, int $price): array
    {
        $receipt = [
            'Taxation' => 'usn_income',
            'Items'    => [[
                'Name'     => "Запись в experience центр",
                'Price'    => $price * 100,
                'Quantity' => 1,
                'Amount'   => $price * 100,
                'Tax'      => 'none',
            ]],
        ];

        if ($client->getEmail()) {
            $receipt['Email'] = $client->getEmail();
        }

        if ($client->getPhone()) {
            $receipt['Phone'] = $client->getPhone();
        }

        return $receipt;
    }
}