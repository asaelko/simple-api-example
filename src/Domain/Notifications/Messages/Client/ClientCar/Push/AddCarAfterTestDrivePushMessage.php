<?php

namespace App\Domain\Notifications\Messages\Client\ClientCar\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;

/**
 * Пуш с просьбой добавить свое авто через час после ТД
 */
final class AddCarAfterTestDrivePushMessage extends AbstractPushMessage
{
    private const TITLE = 'client.client_car.add_car.title';
    private const TEXT = 'client.client_car.add_car.text';

    public function __construct(Client $client)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $client->getFirstName() ? $client->getFirstName() . ', ' : '',
        ];
        $this->receivers = [Client::class => [$client->getId()]];
        $this->data = ['url' => 'https://carl-drive.ru/mycar'];
    }
}
