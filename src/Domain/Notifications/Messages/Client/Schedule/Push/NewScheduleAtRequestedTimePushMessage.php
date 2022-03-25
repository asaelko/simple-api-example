<?php

namespace App\Domain\Notifications\Messages\Client\Schedule\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Model\Model;
use DateTime;

/**
 * Пуш-уведомление о появлении расписания на машину в запрошенный день
 */
final class NewScheduleAtRequestedTimePushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.schedule.notify_about_requested_time.title';
    private const TEXT = 'client.schedule.notify_about_requested_time.text';

    public function __construct(Client $client, Model $model, DateTime $date)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $client->getFirstName() ? $client->getFirstName() . ', ' : '',
            'modelName' => $model->getNameWithBrand(),
            'date' => $date->format('d.m')
        ];

        $this->receivers = [Client::class => [$client->getId()]];
        if ($model->getActiveCar(true)) {
            $this->data = [
                'url' => sprintf('https://carl-drive.ru/car/%d', $model->getActiveCar(true)->getId())
            ];
        }
    }
}
