<?php

namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;

use CarlBundle\Entity\Drive;
use CarlBundle\Helpers\DateFormatterHelper;
use DateTimeZone;

/**
 * Пуш-уведомление водителю об отмене поездки
 */
class TestDriveCancelledTelegramMessage extends AbstractDriverTelegramMessage
{
    private const TEXT = 'тест-драйв %s %s отменен';

    public function __construct(Drive $drive)
    {
        // фиксим таймзону в пуше
        $dateTimezone = new DateTimeZone('Europe/Moscow');
        $driveStart = clone $drive->getStart()->setTimezone($dateTimezone);

        $dateString = sprintf(
            '%d %s %d года',
            $driveStart->format('d'),
            mb_strtolower(DateFormatterHelper::getMonthNameForDate($driveStart)),
            $driveStart->format('Y')
        );

        $message = sprintf(self::TEXT, $driveStart->format('H:i'), $dateString);

        $this->text = $this->addNameToText($drive->getDriver()->getFirstName(), $message);
        $this->driver = $drive->getDriver()->getId();
    }
}
