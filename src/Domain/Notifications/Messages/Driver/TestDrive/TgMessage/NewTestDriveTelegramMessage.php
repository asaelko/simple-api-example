<?php

namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;

use CarlBundle\Entity\Drive;
use DateTimeZone;

/**
 * Уведомление водителя о появлении новой поездки
 */
class NewTestDriveTelegramMessage extends AbstractDriverTelegramMessage
{
    public function __construct(Drive $drive)
    {
        $startDateTime = (clone $drive->getStart())->setTimezone(new DateTimeZone('Europe/Moscow'));
        $messageText = sprintf('у вас появился новый тест-драйв %s', $startDateTime->format('d.m в H:i'));

        $this->text = $this->addNameToText($drive->getDriver()->getFirstName(), $messageText);
        $this->driver = $drive->getDriver()->getId();
    }
}
