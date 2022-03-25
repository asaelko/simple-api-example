<?php

namespace App\Domain\Notifications\Messages\Client\TestDrive\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use CarlBundle\Helpers\DateFormatterHelper;

/**
 * Сообщение о подтверждении ТД со стороны сервиса клиенту
 */
final class TestDriveAcceptedPushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.test_drive.accepted.title';
    private const TEXT = 'client.test_drive.accepted.text';

    public function __construct(Drive $drive)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;

        $this->context = [
            'clientName' => $drive->getClient()->getFirstName() ? $drive->getClient()->getFirstName() . ', ' : '',
            'startDate' => DateFormatterHelper::getPushDate($drive->getStart()),
            'modelName' => $drive->getCar()->getModel()->getNameWithBrand()
        ];

        $this->receivers = [Client::class => [$drive->getClient()->getId()]];
        $this->data = [
            'url' => sprintf('https://carl-drive.ru/drive/%d', $drive->getId())
        ];
    }
}
