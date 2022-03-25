<?php

namespace App\Domain\Infrastructure\AmoCrm\Messenger\Message;

class AmoCanceledDriveMessage
{
    private int $driveId;

    private int $canceledDate;

    public function __construct(
        int $driveId,
        int $canceledDate
    )
    {
        $this->driveId = $driveId;
        $this->canceledDate = $canceledDate;
    }

    public function getDriveId(): int
    {
        return $this->driveId;
    }

    public function getCanceledDate(): int
    {
        return $this->canceledDate;
    }
}