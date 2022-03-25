<?php

namespace App\Domain\EventBus\TestDrive\Event;

use App\Domain\Notifications\NotificationInterface;
use CarlBundle\Entity\Drive;
use RuntimeException;

/**
 * Абстрактное событие в шине событий по поездке
 */
abstract class AbstractDriveNotificationEvent implements NotificationInterface
{
    private int $driveId;

    public function __construct(Drive $drive)
    {
        $driveId = $drive->getId();
        if (!$driveId) {
            throw new RuntimeException('Событие по поездке без id');
        }

        $this->driveId = $driveId;
    }

    /**
     * @return int
     */
    public function getDriveId(): int
    {
        return $this->driveId;
    }
}
