<?php

namespace App\Domain\Notifications\Messages\Client\Schedule\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Model\Model;

/**
 * Пуш-уведомление о появлении расписания на машину
 */
final class NewSchedulePushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.schedule.notify_about_availability.title';
    private const TEXT = 'client.schedule.notify_about_availability.text';

    public function __construct(Client $client, Model $model)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        
        $this->context = [
            'clientName' => $client->getFirstName() ? $client->getFirstName() . ', ' : '',
            'modelName' => $model->getNameWithBrand(),
        ];

        $this->receivers = [Client::class => [$client->getId()]];
        if ($model->getActiveCar(true)) {
            $this->data = [
                'url' => sprintf('https://carl-drive.ru/car/%d', $model->getActiveCar(true)->getId())
            ];
        }
    }
}
