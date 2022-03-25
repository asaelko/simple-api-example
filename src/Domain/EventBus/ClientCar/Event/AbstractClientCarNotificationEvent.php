<?php

namespace App\Domain\EventBus\ClientCar\Event;

use App\Domain\Notifications\NotificationInterface;
use CarlBundle\Entity\ClientCar;
use RuntimeException;

/**
 * Абстрактное событие в шине событий по клиентской машине
 */
class AbstractClientCarNotificationEvent implements NotificationInterface
{
    private int $clientCarId;

    public function __construct(ClientCar $clientCar)
    {
        $clientCarId = $clientCar->getId();
        if (!$clientCarId) {
            throw new RuntimeException('Событие по клиентской машине без id');
        }

        $this->clientCarId = $clientCarId;
    }

    /**
     * @return int
     */
    public function getClientCarId(): int
    {
        return $this->clientCarId;
    }
}
