<?php

namespace App\Domain\Yandex\TurboApp\Service;

use CarlBundle\Entity\Drive;
use CarlBundle\Entity\Payment\Payment;
use CarlBundle\Entity\Prebooking\PrebookingSession;
use CarlBundle\Service\Drive\DrivePaymentService;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class KassaService
{
    private LoggerInterface $logger;

    private DrivePaymentService $drivePaymentService;

    private $apiKey;

    private $apiUrl;

    public function __construct(
        LoggerInterface $yandexLogger,
        ParameterBagInterface $parameterBag,
        DrivePaymentService $drivePaymentService
    )
    {
        $this->drivePaymentService = $drivePaymentService;
        $this->logger = $yandexLogger;
        $kassaConfig = $parameterBag->get('yandex')['kassa'];
        $this->apiKey = $kassaConfig['api_key'];
        $this->apiUrl = $kassaConfig['api_url'];
    }

    /**
     * @param Drive             $drive
     * @param PrebookingSession $session
     *
     * @return array
     */
    public function createYandexKassaPayment(Drive $drive, PrebookingSession $session): array
    {
        $data = [
            'caption'                  => 'Покупка в CARL',
            'description'              => sprintf(
                'Оплата за %s',
                $this->drivePaymentService->getProductName($drive)
            ),
            'meta'                     => $session->getId(),
            'autoclear'                => true,
            'items'                    => [
                [
                    'name'     => $drive->getCar()->getCarModelBrandName(),
                    'price'    => $drive->getDriveRate()->getPrice(),
                    'nds'      => 'nds_none',
                    'currency' => 'RUB',
                    'amount'   => 1,
                ],
            ],
            'pay_method'               => null,
            'offline_abandon_deadline' => (new DateTime('+20 minute'))->format('Y-m-d\TH:i:sO'),
            'return_url'               => null,
        ];

        return $this->request('POST', 'order', $data);
    }

    /**
     * Возвращаем платеж
     *
     * @param array $paymentData
     *
     * @return array
     */
    public function returnPayment(array $paymentData): array
    {
        $orderInfo = $this->request('GET', 'order/' . $paymentData['order_id']);
        $items = $orderInfo['data']['items'] ?? [];

        $data = [
            'caption'     => 'Возврат денег за заказ #' . $paymentData['order_id'],
            'description' => 'Отмена платежа в связи с невозможностью забронировать тест-драйв',
            'meta'        => $paymentData['order_id'],
            'items'       => $items,
        ];
        return $this->request('POST', 'order/' . $paymentData['order_id'] . '/refund', $data);
    }

    /**
     * @param string $type
     * @param string $method
     * @param array  $body
     *
     * @return array
     * @throws GuzzleException
     */
    public function request(string $type = 'POST', string $method = '/', array $body = []): array
    {
        try {
            $client = new Client(
                [
                    'base_uri' => $this->apiUrl,
                ]
            );

            $response = $client->request(
                $type,
                $method,
                [
                    'headers' => [
                        'Authorization' => $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json'    => $body,
                ]
            );

            $data = $response->getBody()->getContents();
            $this->logger->info('Order payment response: ' . $data);
            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return [];
        }
    }

    public function createPaymentEntity(array $paymentData, PrebookingSession $prebookingSession): Payment
    {
        $payment = new Payment();
        $paymentData = $paymentData['data'];
        $payment->setPaymentStatus($paymentData['pay_status']);
        $payment->setClient($prebookingSession->getClient());
        $payment->setAmount($paymentData['price']);
        $payment->setCreatedAt(new DateTime());
        $payment->setOrderId($paymentData['order_id']);
        $payment->setPaymentId($paymentData['order_id']);
        $payment->setPaymentTerminal('yandex');
        $payment->setTarget($prebookingSession);

        return $payment;
    }
}
