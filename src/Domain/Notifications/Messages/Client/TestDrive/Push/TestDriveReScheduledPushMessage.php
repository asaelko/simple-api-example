<?php

namespace App\Domain\Notifications\Messages\Client\TestDrive\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use CarlBundle\Helpers\DateFormatterHelper;

/**
 * Уведомление о переносе ТД консультантами
 */
class TestDriveReScheduledPushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.test_drive.rescheduled.title';
    private const TEXT = 'client.test_drive.rescheduled.text';

    public function __construct(Drive $drive)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;

        $this->context = [
            'clientName' => $drive->getClient()->getFirstName() ? $drive->getClient()->getFirstName() . ', ' : '',
            'modelName' =>  $drive->getCar()->getModel()->getNameWithBrand(),
            'startDate' => DateFormatterHelper::getPushDate($drive->getStart())
        ];

        $this->receivers = [Client::class => [$drive->getClient()->getId()]];
        $this->data = [
            'url' => sprintf('https://carl-drive.ru/drive/%d', $drive->getId())
        ];
    }
}
