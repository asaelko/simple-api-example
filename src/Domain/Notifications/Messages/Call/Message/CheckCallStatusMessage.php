<?php

namespace App\Domain\Notifications\Messages\Call\Message;

class CheckCallStatusMessage
{
    private int $driveId;

    private string $callId;

    public function __construct(
        int $driverId,
        string $callId
    )
    {
        $this->callId = $callId;
        $this->driveId = $driverId;
    }

    public function getCallId(): string
    {
        return $this->callId;
    }

    public function getDriveId(): int
    {
        return $this->driveId;
    }
}