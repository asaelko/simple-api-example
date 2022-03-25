<?php

namespace App\Domain\Notifications\Messages\Client\TestDrive\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;

/**
 * Пуш с просьбой оставить фидбек о поездке
 */
final class TestDriveFeedbackPushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.test_drive.feedback.title';
    private const TEXT = 'client.test_drive.feedback.text';

    public function __construct(Drive $drive)
    {
        $this->title = sprintf(self::TITLE);
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $drive->getClient()->getFirstName() ? $drive->getClient()->getFirstName() . ', ' : '',
            'modelName' => $drive->getCar()->getModel()->getNameWithBrand(),
        ];
        $this->receivers = [Client::class => [$drive->getClient()->getId()]];
        $this->data = [
            'url' => sprintf('https://carl-drive.ru/drive/%d', $drive->getId())
        ];
    }
}
