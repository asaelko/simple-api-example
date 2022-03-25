<?php

namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;

use CarlBundle\Entity\Drive;

/**
 * Уведомление водителю о том, что поездка длится слишком долго
 */
class TooLongTestDriveReminderTelegramMessage extends AbstractDriverTelegramMessage
{
    private const TEXT = 'Поездка длится слишком долго, все ли в порядке?';

    public function __construct(Drive $drive)
    {
        $this->text = $this->addNameToText($drive->getDriver()->getFirstName(), self::TEXT);
        $this->driver = $drive->getDriver()->getId();
    }
}
