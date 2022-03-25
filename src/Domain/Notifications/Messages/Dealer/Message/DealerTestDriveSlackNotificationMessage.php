<?php


namespace App\Domain\Notifications\Messages\Dealer\Message;


class DealerTestDriveSlackNotificationMessage
{
    private int $userId;

    private int $modelId;

    private int $dealerId;

    private \DateTimeInterface $dateTestDrive;

    private int $event;

    public function __construct(
        int $userId,
        int $modelId,
        int $dealerId,
        \DateTimeInterface $dateTestDrive,
        int $event
    )
    {
        $this->dealerId = $dealerId;
        $this->userId = $userId;
        $this->modelId = $modelId;
        $this->dateTestDrive = $dateTestDrive;
        $this->event = $event;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getModelId(): int
    {
        return $this->modelId;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->dateTestDrive;
    }

    public function getDealerId(): int
    {
        return $this->dealerId;
    }

    public function getEvent(): int
    {
        return $this->event;
    }
}