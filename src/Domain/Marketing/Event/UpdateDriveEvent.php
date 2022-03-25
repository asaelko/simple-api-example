<?php

namespace App\Domain\Marketing\Event;

use CarlBundle\Entity\Drive;
use DateTime;
use RuntimeException;

/**
 * Событие обновления поездки для маркетингового деша
 */
class UpdateDriveEvent
{
    private int $driveId;
    private DateTime $updatedAt;

    public function __construct(Drive $drive, DateTime $updatedAt)
    {
        $driveId = $drive->getId();
        if (!$driveId) {
            throw new RuntimeException('Нельзя обновить данные по поездке без идентификатора');
        }

        $this->driveId = $driveId;
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return int
     */
    public function getDriveId(): int
    {
        return $this->driveId;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}
