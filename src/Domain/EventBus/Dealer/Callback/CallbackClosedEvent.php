<?php

namespace App\Domain\EventBus\Dealer\Callback;

use CarlBundle\EventBus\BusEventInterface;
use DealerBundle\Entity\CallbackAction;

/**
 * Событие закрытия заявки на коллбек
 */
class CallbackClosedEvent implements BusEventInterface
{
    public int $callbackId;

    public function __construct(CallbackAction $callback)
    {
        $this->callbackId = $callback->getId();
    }
}
