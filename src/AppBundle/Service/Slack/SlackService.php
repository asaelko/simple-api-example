<?php

namespace AppBundle\Service\Slack;

use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Класс для работы со slack-ом через очереди отправки
 */
class SlackService
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(
        MessageBusInterface $messageBus,
        LoggerInterface $slackLogger
    )
    {
        $this->logger = $slackLogger;
        $this->messageBus = $messageBus;
    }

    /**
     * Отправка сообщения
     *
     * @param SlackMessage $slackMessage
     * @return bool
     */
    public function sendMessage(SlackMessage $slackMessage): bool
    {
        $stamps = [];
        if ($slackMessage->getSendDate()) {
            $delay = $slackMessage->getSendDate()->getTimestamp() - (new DateTime)->getTimestamp();
            $stamps[] = new DelayStamp($delay * 1000);
        }

        $this->messageBus->dispatch($slackMessage, $stamps);
        return true;
    }
}
