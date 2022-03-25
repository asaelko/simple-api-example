<?php

namespace App\Domain\Notifications\Messages\Client\ClientCar\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\ClientCar;

/**
 * Пуш-уведомление о том, что клиентский автомобиль был отклонен модератором
 */
final class ClientCarDeclinedPushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.client_car.declined.title';
    private const TEXT = 'client.client_car.declined.text';

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
