<?php

namespace AppBundle\MessageHandler;

use AppBundle\Service\Slack\SlackClient;
use AppBundle\Service\Slack\SlackMessage;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Throwable;

/**
 * Обработчик событий очереди Slack сообщений
 */
class SlackMessageHandler implements MessageHandlerInterface
{
    /**
     * @var SlackClient
     */
    private $slackClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SlackClient $slackClient
     * @param LoggerInterface $slackLogger
     */
    public function __construct(
        SlackClient $slackClient,
        LoggerInterface $slackLogger
    )
    {
        $this->slackClient = $slackClient;
        $this->logger = $slackLogger;
    }

    public function __invoke(SlackMessage $message)
    {
        $messageContent = json_encode($message);
        $messagePayload = $message->jsonSerialize();

        try {
            $resultSend = $this->slackClient->sendMessage($messagePayload['receiver'], $messagePayload['message']);
            if (!$resultSend) {
                $this->logger->critical(sprintf('Error send message to slack: %s', $messageContent));
            }
        } catch (Throwable|GuzzleException $exception) {
            $this->logger->critical(sprintf('Error send message to slack: %s, message: %s', $exception->getMessage(), $messageContent));
        }

        $this->logger->info(sprintf('Slack message was sent: %s', $messageContent));
    }
}
