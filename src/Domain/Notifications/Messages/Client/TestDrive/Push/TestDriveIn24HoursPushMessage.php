<?php

namespace App\Domain\Notifications\Messages\Client\TestDrive\Push;

use App\Domain\Notifications\NotificationInterface;
use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use DateTimeZone;

/**
 * Пуш-уведомление клиенту о том, что до тест-драйва осталось 24 часа
 */
final class TestDriveIn24HoursPushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.test_drive.in_24_hours.title';
    private const TEXT = 'client.test_drive.in_24_hours.text';

    public function __construct(Drive $drive)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;

        $startDate = clone $drive->getStart();

        $hour = (int)$startDate->setTimezone(
            new DateTimeZone(NotificationInterface::NOTIFICATION_TIMEZONE)
        )->format('H');

        $hourText = 'часов';

        if ($hour === 1 || $hour === 21) {
            $hourText = 'час';
        } elseif ($hour < 5 || $hour > 21) {
            $hourText = 'часа';
        }

        $this->context = [
            'clientName' => $drive->getClient()->getFirstName() ? $drive->getClient()->getFirstName() . ', ' : '',
            'startDate' => sprintf('%s %s', $hour, $hourText),
            'modelName' => $drive->getCar()->getModel()->getNameWithBrand(),
        ];

        $this->receivers = [Client::class => [$drive->getClient()->getId()]];
        $this->data = [
            'url' => sprintf('https://carl-drive.ru/drive/%d', $drive->getId())
        ];
    }
}
