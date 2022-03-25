<?php

namespace AppBundle\Service\Slack;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SlackClient
{
    /**
     * @var string
     */
    private $slackApiUrl;

    /**
     * @var string
     */
    private $slackBotToken;

    /**
     * @var string
     */
    private $slackBotUsername;

    /**
     * @var LoggerInterface
     */
    private $Logger;

    public function __construct(
        LoggerInterface $logger,
        string $slackApiUrl,
        string $slackBotUsername,
        string $slackBotToken
    ) {
        $this->Logger = $logger;

        $this->slackBotUsername = $slackBotUsername;
        $this->slackBotToken    = $slackBotToken;
        $this->slackApiUrl      = $slackApiUrl;
    }

    /**
     * @param string $channelName
     * @param string $text
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMessage(string $channelName, string $text): bool
    {
        $this->Logger->info(
            sprintf('Call %s with text: "%s", channelName: "%s"', __METHOD__, $text, $channelName)
        );

        return $this->request(
            'POST',
            'chat.postMessage',
            [
                'channel' => $channelName,
                'text' => $text,
            ]
        );
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $requestData
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request(string $method, string $uri, array $requestData)
    {
        $client = $client = new Client(['base_uri' => $this->slackApiUrl]);

        try {
            $response = $client->request(
                $method,
                $uri,
                [
                    'headers' => [
                        'Content-type' => 'application/json; charset=utf-8',
                        'Authorization' => 'Bearer ' . $this->slackBotToken,
                    ],
                    'json' => $requestData,
                ]
            );

            $content = $response->getBody()->getContents();
            $json = json_decode($content, true, JSON_THROW_ON_ERROR);

            if ($json['ok'] === false) {
                throw new RuntimeException($json['error'] ?? '');
            }

        } catch (\Exception $Exception) {
            $this->Logger->critical(
                sprintf(
                    'Error in request to slack. Method: %s, uri: %s, requestData: %s, errorMessage: %s',
                    $method,
                    $uri,
                    var_export($requestData, true),
                    $Exception->getMessage()
                )
            );

            throw $Exception;
        }

        return true;
    }
}
