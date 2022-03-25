<?php


namespace App\Messenger\Messages\Sse;


class SsePaymentNotificationMessage
{
    private string $transactionId;

    private int $transactionState;

    public function __construct(
        string $transactionId,
        int $transactionState
    )
    {
        $this->transactionId = $transactionId;
        $this->transactionState = $transactionState;
    }

    public function getTransactionState(): int
    {
        return $this->transactionState;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }
}