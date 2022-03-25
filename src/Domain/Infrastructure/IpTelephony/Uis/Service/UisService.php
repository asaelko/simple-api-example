<?php

namespace App\Domain\Infrastructure\IpTelephony\Uis\Service;

use App\Domain\Infrastructure\IpTelephony\Uis\Helper\UisAuthHelper;
use App\Domain\Infrastructure\IpTelephony\Uis\Request\ProcessingCallActionRequest;
use App\Entity\Client\PhoneCall;
use CarlBundle\Entity\Drive;
use CarlBundle\Entity\Driver;
use CarlBundle\Service\Media\MediaUploadService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UisService
{
    private ?string $key = null;

    private ?int $expiredAt = null;

    private UisAuthHelper $helper;

    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    private MediaUploadService $mediaService;

    private string $urlData;

    private string $urlCall;

    private string $uisCallApiKey;

    private string $phone;

    private int $callCenterId;

    private string $callCenterPhone;

    public function __construct(
        UisAuthHelper $helper,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        MediaUploadService $mediaService,
        LoggerInterface $uisLogger
    )
    {
        $this->helper = $helper;
        $this->mediaService = $mediaService;
        $cred = $parameterBag->get('uis');
        $this->urlData = $cred['url_data'] . $cred['version_data'];
        $this->urlCall = $cred['url_call'] . $cred['version_call'];
        $this->uisCallApiKey = $cred['call_api_key'];
        $this->entityManager = $entityManager;
        $this->phone = $cred['call_number'];
        $this->logger = $uisLogger;
        $this->callCenterId = $cred['call_center_id'];
        $this->callCenterPhone = $cred['call_center_phone'];
    }

    protected function getKey(): ?string
    {
        if (!$this->key || ($this->key && $this->expiredAt && $this->expiredAt <= time())) {
            $data = $this->helper->login();
            if (!$data) {
                throw new RuntimeException('Ошибка авторизации UIS');
            }
            $this->key = $data['key'];
            $this->expiredAt = $data['expire_at'];
        }
        return $this->key;
    }

    public function createEmployer(Driver $user): bool
    {
        $data = [
            "jsonrpc" => "2.0",
            "id" => time(),
            "method" => "create.employees",
            "params" => [
                "access_token" => $this->getKey(),
                "first_name" => $user->getFirstName(),
                "last_name" => $user->getLastName(),
                "status" => "available",
                "in_external_allowed_call_directions" => ["in", "out"],
                "in_internal_allowed_call_directions" => ["in", "out"],
                "call_recording" => "all",
                "phone_numbers" => [
                    [
                        "phone_number" => $user->getPhone()
                    ],
                ]
            ]
        ];

        try {

            $client = new Client();
            $response = $client->post($this->urlData,
                [
                    'json' => $data
                ]
            );

            $result = $response->getBody()->getContents();
            $result = json_decode($result, 1);

            $user->setUisId($result['result']['data']['id']);
            $user->setUisPhone($user->getPhone());
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->warning($e);
        }
        return true;
    }

    public function removeEmployer(Driver $user): bool
    {
        $data = [
            "jsonrpc" => "2.0",
            "id" => time(),
            "method" => "delete.employees",
            "params" => [
                "access_token" => $this->getKey(),
                "id" => $user->getUisId()
            ]
        ];

        $client = new Client();
        $response = $client->post($this->urlData,
            [
                'json' => $data
            ]
        );
        return true;
    }

    public function createCallToUser(Drive $drive): ?string
    {
        if (!$drive->getDriver()->getUisId()) {
            return null;
        }

        $data = [
            "jsonrpc" => "2.0",
            "method" => "start.employee_call",
            "id" => time(),
            "params" => [
                "access_token" => $this->uisCallApiKey,
                "first_call" => "employee",
                "switch_at_once" => true,
                "early_switching" => true,
                "virtual_phone_number" => $this->phone,
                "show_virtual_phone_number" => true,
                "contact" => preg_replace('/\D+/', '', $drive->getClient()->getPhone()),
                "employee" => [
                    "id" => (int) $drive->getDriver()->getUisId(),
                ]
            ]
        ];
        try {
            $client = new Client();
            $response = $client->post($this->urlCall,
                [
                    'json' => $data
                ]
            );

            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            return $result['result']['data']['call_session_id'] ?? null;
        } catch (\Exception $e) {
            $this->logger->warning($e);
            return null;
        }
    }

    /**
     * @param string $phone
     * @return bool
     */
    public function makeCallToCallCenter(string $phone): ?string
    {
        $data = [
            "jsonrpc" => "2.0",
            "method" => "start.employee_call",
            "id" => time(),
            "params" => [
                "access_token" => $this->uisCallApiKey,
                "first_call" => "employee",
                "switch_at_once" => true,
                "early_switching" => true,
                "virtual_phone_number" => $this->phone,
                "show_virtual_phone_number" => true,
                "contact" => preg_replace('/\D+/', '', $phone),
                "employee" => [
                    "id" => (int) $this->callCenterId,
                    "phone_number" => $this->callCenterPhone,
                ]
            ]
        ];

        try {
            $client = new Client();
            $response = $client->post($this->urlCall,
                [
                    'json' => $data
                ]
            );

            $result = $response->getBody()->getContents();
            $result = json_decode($result, 1);
            return $result['result']['data']['call_session_id'] ?? null;
        } catch (\Exception $e) {
            $this->logger->warning($e);
            return null;
        }
    }

    public function getCallInformation(string $callSessionId, \DateTime $start, \DateTime $end): ?array
    {
        $data = [
            "jsonrpc" => "2.0",
            "method" => "get.calls_report",
            "id" => time(),
            "params" => [
                "access_token" => $this->uisCallApiKey,
                "date_from" => $start->format('Y-m-d H:i:s'),
                "date_till" => $end->format('Y-m-d H:i:s'),
                "filter" => [
                    "field" => "id",
                    "operator" => "=",
                    "value" => (int) $callSessionId
                ]
            ]
        ];
        try {
            $client = new Client();
            $response = $client->post($this->urlData,
                [
                    'json' => $data
                ]
            );

            $result = $response->getBody()->getContents();
            $result = json_decode($result, 1);
            return $result['result']['data'][0] ?? null;
        } catch (\Exception $e) {
            $this->logger->warning($e);
            return null;
        }
    }

    public function processingCallEntity(ProcessingCallActionRequest $request, \CarlBundle\Entity\Client $client): bool
    {
        $call = new PhoneCall();
        $call->setClient($client);
        $call->setCallId($request->call_id);

        $file = file_get_contents($request->link);

        $media = $this->mediaService->uploadFilePrivateBucket(
            $file,
            $request->call_id,
            'mp3',
            'audio/mpeg',
            '/calls/records/'
        );

        $call->setRecord($media);
        $this->entityManager->persist($call);
        $this->entityManager->flush();
        return true;
    }
}