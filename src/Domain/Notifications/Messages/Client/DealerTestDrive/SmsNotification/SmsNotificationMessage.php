<?php

namespace App\Domain\Notifications\Messages\Client\DealerTestDrive\SmsNotification;

class SmsNotificationMessage
{
    private int $clientId;

    private int $dealerId;

    private int $state;

    private \DateTime $testDriveDate;

    public function __construct(
        int $clientId,
        int $dealerId,
        int $state,
        \DateTime $testDriveDate
    )
    {
        $this->clientId = $clientId;
        $this->dealerId = $dealerId;
        $this->state = $state;
        $this->testDriveDate = $testDriveDate;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getDealerId(): int
    {
        return $this->dealerId;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function getTestDriveDate(): \DateTime
    {
        return $this->testDriveDate;
    }
}