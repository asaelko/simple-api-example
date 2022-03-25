<?php

namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;

use CarlBundle\Entity\Drive;

/**
 * Пуш-уведомление водителю о необходимости подтвердить поездку
 */
class TestDriveApproveReminderTelegramMessage extends AbstractDriverTelegramMessage
{
    private const TEXT = 'Не забудьте подтвердить тест драйв у клиента %s %s';

    public function __construct(Drive $drive)
    {
        $message = sprintf(
            self::TEXT,
            $drive->getClient()->getFirstName(),
            $drive->getClient()->getSecondName()
        );

        $this->text = $this->addNameToText($drive->getDriver()->getFirstName(), $message);
        $this->driver = $drive->getDriver()->getId();
    }
}
