<?php

namespace App\Domain\Notifications\Push\Sender\GoRush;

use App\Domain\Notifications\Push\Builder\PushNotificationBuilder;
use App\Domain\Notifications\Push\PushMessageInterface;
use App\Domain\Notifications\Push\Sender\PushSenderInterface;
use AppBundle\Service\AppConfig;
use CarlBundle\Entity\PushMessages;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Провайдер отправки пушей через GoRush
 */
class GoRushPushSender implements PushSenderInterface
{
    private const MOBILE_PLATFORMS = [
        PushMessages::OS_IOS              => 1,
        PushMessages::OS_FIREBASE_IOS     => 2,
        PushMessages::OS_FIREBASE_ANDROID => 2,
    ];

    private Client $client;
    private LoggerInterface $logger;
    private ParameterBagInterface $parameterBag;
    private AppConfig $appConfig;
    private PushNotificationBuilder $pushBuilder;
    private TranslatorInterface $translator;

    public function __construct(
        Client $client,
        LoggerInterface $pushLogger,
        ParameterBagInterface $parameterBag,
        AppConfig $appConfig,
        PushNotificationBuilder $pushBuilder,
        TranslatorInterface $translator
    )
    {
        $this->client = $client;
        $this->logger = $pushLogger;
        $this->parameterBag = $parameterBag;
        $this->appConfig = $appConfig;
        $this->pushBuilder = $pushBuilder;
        $this->translator = $translator;
    }

    /**
     * Обрабатываем пуш-сообщения
     *
     * @param PushMessageInterface $pushMessage
     * @return bool
     */
    public function processPush(PushMessageInterface $pushMessage): bool
    {
        $goRushPush = $this->pushBuilder->build($pushMessage);
        if (!$goRushPush) {
            return false;
        }
        try {
            $this->sendPush($goRushPush);
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Отправляем пуш в gorush
     *
     * @param PushMessage $push
     * @throws GuzzleException
     * @throws JsonException
     * @throws Exception
     */
    private function sendPush(PushMessage $push): void
    {
        $payload = [
            'notifications' => [],
        ];
        if (!$push->getUuid()) {
            $push->setUuid(Uuid::uuid4());
        }

        foreach ($push->getReceivers() as $appTag => $receivers) {
            foreach ($receivers as $mobilePlatform => $recipientsTokens) {
                $platformCode = self::MOBILE_PLATFORMS[$mobilePlatform] ?? null;

                if (!$platformCode) {
                    $this->logger->error('No such platform as ' . $mobilePlatform);
                    continue;
                }

                $notification = [
                    'notif_id' => $push->getUuid(),
                    'tokens'   => $recipientsTokens,
                    'platform' => $platformCode,
                ];

                if ($mobilePlatform === PushMessages::OS_IOS) {
                    $this->addIOsPayload($notification, $appTag, $push);
                } else {
                    $this->addAndroidPayload($notification, $appTag, $push);
                }

                $payload['notifications'][] = $notification;
            }
        }

        $this->logger->info(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        $response = $this->client->request(
            'POST',
            $this->parameterBag->get('gorush_service_url'),
            [
                RequestOptions::JSON => $payload,
            ]
        );

        $this->logger->info($response->getBody()->getContents());
    }

    /**
     * @param array $notification
     * @param string $appTag
     * @param PushMessage $push
     */
    private function addIOsPayload(array &$notification, string $appTag, PushMessage $push): void
    {
        $locale = 'ru_' . $appTag;
        if ($push->getTitle()) {
            $notification['alert'] = [
                'title' => $this->translator->trans($push->getTitle(), $push->getContext(), 'push_notifications', $locale),
                'body'  => $this->translator->trans($push->getBody(), $push->getContext(), 'push_notifications', $locale)
            ];
        } else {
            $notification['message'] = $this->translator->trans($push->getBody(), $push->getContext(), 'push_notifications', $locale);
        }

        $notification['topic'] = $this->appConfig->getWlConfig($appTag)['apns']['topic'] ?? 'ru.carl.drive.app';
        $notification['production'] = true;
        $notification['sound'] = [
            'name'   => 'sound1.wav',
            'volume' => 1.0
        ];

        if ($push->getData()) {
            $notification['data']['data'] = $push->getData();
        }
        if ($push->getImage()) {
            $notification['data']['image_url'] = $push->getImage();
        }
    }

    /**
     * @param $notification
     * @param string $appTag
     * @param PushMessage $push
     */
    private function addAndroidPayload(&$notification, string $appTag, PushMessage $push): void
    {
        $locale = 'ru_' . $appTag;
        $notification['message'] = $this->translator->trans($push->getBody(), $push->getContext(), 'push_notifications', $locale);

        $notification['sound'] = 'sound1';

        if ($push->getImage()) {
            $notification['image'] = $push->getImage();
        }

        if ($push->getTitle()) {
            $notification['title'] = $this->translator->trans($push->getTitle(), $push->getContext(), 'push_notifications', $locale);
        }

        if (isset($this->appConfig->getWlConfig($appTag)['firebase'])) {
            $firebaseConfig = $this->appConfig->getWlConfig($appTag)['firebase'];
            $notification['api_key'] = $firebaseConfig['auth_key'];
            $notification['restricted_package_name'] = $firebaseConfig['package_name'];
        }

        if ($push->getData()) {
            $notification['data'] = $push->getData();
        }
    }
}
