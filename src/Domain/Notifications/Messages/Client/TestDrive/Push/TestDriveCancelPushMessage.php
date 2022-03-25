<?php

namespace App\Domain\Notifications\Messages\Client\TestDrive\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;

/**
 * Сообщение клиенту об отмене тест-драйва
 */
final class TestDriveCancelPushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.test_drive.cancelled.title';
    private const TEXT = 'client.test_drive.cancelled.text';

    public function __construct(Drive $drive)
    {
        $model = $drive->getCar()->getModel();

        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $drive->getClient()->getFirstName() ? $drive->getClient()->getFirstName() . ', ' : '',
            'modelName' => $model->getNameWithBrand(),
        ];

        $this->receivers = [Client::class => [$drive->getClient()->getId()]];
        $this->data = [
            'url' => sprintf('https://carl-drive.ru/drive/%d', $drive->getId())
        ];
    }
}
