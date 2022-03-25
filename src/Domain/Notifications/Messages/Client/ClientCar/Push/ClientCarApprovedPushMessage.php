<?php

namespace App\Domain\Notifications\Messages\Client\ClientCar\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\ClientCar;

/**
 * Пуш-уведомление о том, что клиентский автомобиль был подтвержден модератором
 */
final class ClientCarApprovedPushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.client_car.approved.title';
    private const TEXT = 'client.client_car.approved.text';

    public function __construct(ClientCar $car)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $car->getClient()->getFirstName() ? $car->getClient()->getFirstName() . ', ' : '',
            'modelName' => $car->getModel()->getNameWithBrand()
        ];
        $this->receivers = [Client::class => [$car->getClient()->getId()]];
        $this->data = ['url' => 'https://carl-drive.ru/mycar'];
    }
}
