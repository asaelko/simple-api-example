<?php


namespace App\Domain\Notifications\Messages\Drive\Message;


class OnDriveUpCheckCameraNotificationMessage
{
    private int $driveId;

    public function __construct(int $driveId)
    {
        $this->driveId = $driveId;
    }

    public function getDriveId(): int
    {
        return $this->driveId;
    }
}