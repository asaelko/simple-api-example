<?php

namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;

use CarlBundle\Entity\Drive;
use CarlBundle\Helpers\DateFormatterHelper;
use DateTimeInterface;
use DateTimeZone;

/**
 * Пуш-уведомление водителю об изменении времени поездки
 */
class TestDriveDateChangedTelegramMessage extends AbstractDriverTelegramMessage
{
    private const TEXT = 'Ваша поездка в %s %s перенесена на %s %s';

    public function __construct(Drive $drive, DateTimeInterface $oldDate, DateTimeInterface $newDate)
    {
        $oldStart = clone $oldDate;
        $newStart = clone $newDate;

        $dateTimeZone = new DateTimeZone('Europe/Moscow');
        $oldStart->setTimezone($dateTimeZone);
        $newStart->setTimezone($dateTimeZone);

        $oldDateString = sprintf(
            '%d %s %d года',
            $oldStart->format('d'),
            mb_strtolower(DateFormatterHelper::getMonthNameForDate($oldStart)),
            $oldStart->format('Y')
        );

        $newDateString = sprintf(
            '%d %s %d года',
            $newStart->format('d'),
            mb_strtolower(DateFormatterHelper::getMonthNameForDate($newStart)),
            $newStart->format('Y')
        );

        $message = sprintf(
            self::TEXT,
            $oldStart->format('H:i'),
            $oldDateString,
            $newStart->format('H:i'),
            $newDateString
        );

        $this->text = $this->addNameToText($drive->getDriver()->getFirstName(), $message);
        $this->driver = $drive->getDriver()->getId();
    }
}
