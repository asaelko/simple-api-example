<?php

namespace App\Domain\Yandex\TurboApp;

use Exception;
use GuzzleHttp\Client as HttpClient;
use Psr\Log\LoggerInterface;

class ApiClient
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $yandexLogger;

    public function __construct(
        LoggerInterface $yandexLogger
    )
    {
        $this->yandexLogger = $yandexLogger;
    }

    /**
     * Запрашиваем у Яндекса данные по авторизации
     *
     * @param string $token
     * @return array|null
     */
    public function requestAuthData(string $token): ?array
    {
        try {
            $guzzleClient = new HttpClient();
            $response = $guzzleClient->get(
                'https://login.yandex.ru/info',
                [
                    'query' => [
                        'format'               => 'json',
                        'with_openid_identity' => true,
                        'oauth_token'          => $token
                    ]
                ]
            );

            return json_decode($response->getBody(), 1, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            $this->yandexLogger->error($e->getMessage());
            return null;
        }
    }
}