<?php

namespace App\Domain\Notifications\Messages\Client\TestDrive\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;

/**
 * Пуш-оповещение клиента о том, что водитель подъезжает к месту тест-драйва
 */
final class TestDriveDriverNearbyPushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.test_drive.driver_nearby.title';
    private const TEXT = 'client.test_drive.driver_nearby.text';

    public function __construct(Drive $drive)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;

        $this->context = [
            'clientName' => $drive->getClient()->getFirstName() ? $drive->getClient()->getFirstName() . ', ' : '',
            'driverName' => $drive->getDriver()->getFirstName() ? $drive->getDriver()->getFirstName() . ' ' : '',
            'modelName' => $drive->getCar()->getModel()->getNameWithBrand()
        ];

        $this->receivers = [Client::class => [$drive->getClient()->getId()]];
        $this->data = [
            'url' => sprintf('https://carl-drive.ru/drive/%d', $drive->getId())
        ];
    }
}
