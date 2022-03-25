<?php

namespace App\Messenger\Handlers\Sse;

use App\Messenger\Messages\Sse\SsePaymentNotificationMessage;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SsePaymentNotificationHandler implements MessageHandlerInterface
{
    private PublisherInterface $publisher;
    private LoggerInterface $logger;

    public function __construct(
        PublisherInterface $publisher,
        LoggerInterface $logger
    )
    {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    public function __invoke(SsePaymentNotificationMessage $message): bool
    {
        $update = new Update(
            $message->getTransactionId(),
            json_encode(['status' => $message->getTransactionState()])
        );

        try {
            $publisher = $this->publisher;
            $publisher($update);
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }

        return true;
    }
}
