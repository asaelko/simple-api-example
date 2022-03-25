<?php

namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;

use CarlBundle\Entity\Drive;
use CarlBundle\Helpers\DateFormatterHelper;
use DateTimeZone;

class NewCommentToDriverTelegramMessage extends AbstractDriverTelegramMessage
{
    private const TEXT = 'У вас появился новый комментарий по поездке %s на %s: "%s"';

    public function __construct(Drive $drive)
    {
        $dateTimezone = new DateTimeZone('Europe/Moscow');
        $driveStart = clone $drive->getStart()->setTimezone($dateTimezone);

        $dateString = sprintf(
            '%d %s',
            $driveStart->format('H:i d'),
            DateFormatterHelper::getMonthNameByNumber($driveStart->format('n'), true)
        );

        $text = sprintf(
            self::TEXT,
            $dateString,
            $drive->getCar()->getModel()->getNameWithBrand(),
            $drive->getClientComment()
        );

        $this->text = $this->addNameToText($drive->getDriver()->getFirstName(), $text);
        $this->driver = $drive->getDriver()->getId();
    }
}
