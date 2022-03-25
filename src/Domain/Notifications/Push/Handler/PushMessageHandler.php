<?php

namespace App\Domain\Notifications\Push\Handler;

use App\Domain\Notifications\Push\PushMessageInterface;
use App\Domain\Notifications\Push\Sender\PushSenderInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Обработчик пушей, пришедших из очереди
 */
class PushMessageHandler implements MessageHandlerInterface
{
    private PushSenderInterface $pushSender;

    public function __construct(
        PushSenderInterface $pushSender
    )
    {
        $this->pushSender = $pushSender;
    }

    public function __invoke(PushMessageInterface $pushMessage)
    {
        $this->pushSender->processPush($pushMessage);
    }
}
