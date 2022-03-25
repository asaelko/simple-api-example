<?php

namespace App\Domain\Infrastructure\IpTelephony\Uis\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UisAuthHelper
{
    private string $login;

    private string $password;

    private string $url;

    private LoggerInterface $logger;

    public function __construct(
        ParameterBagInterface $parameters,
        LoggerInterface $logger
    )
    {
        $cred = $parameters->get('uis');
        $this->login = $cred['login'];
        $this->password = $cred['password'];
        $this->url = $cred['url_data'] . $cred['version_data'];
        $this->logger = $logger;
    }

    public function login(): ?array
    {
        try {
            $client = new Client(['base_uri' => $this->url]);
            $data = [
                "jsonrpc" => "2.0",
                "id" => "req1",
                "method" => "login.user",
                "params" => [
                    "login" => $this->login,
                    "password" => $this->password,
                ]
            ];

            $result = $client->post(
                '',
                [
                    'json' => $data
                ]
            );
            $data = $result->getBody()->getContents();
            $data = json_decode($data,1);
            return [
                'key' => $data['result']['data']['access_token'],
                'expire_at' => $data['result']['data']['expire_at']
            ];
        } catch (ClientException $e) {
            $this->logger->error($e->getResponse()->getBody());
            return null;
        }
    }
}