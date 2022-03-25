<?php

namespace App\Domain\Notifications\Messages\Client\TestDrive\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;

/**
 * Пуш-уведомление о начале поездки (этап оформления документов)
 */
final class TestDriveIsIssuingPushMessage extends AbstractPushMessage
{
    public const TITLE = 'client.test_drive.issuing.title';
    public const TEXT = 'client.test_drive.issuing.text';

    public function __construct(Drive $drive)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $drive->getClient()->getFirstName() ? $drive->getClient()->getFirstName() . ', ' : '',
        ];

        $this->receivers = [Client::class => [$drive->getClient()->getId()]];
        $this->data = [
            'url' => sprintf('https://carl-drive.ru/drive/%d', $drive->getId())
        ];
    }
}
