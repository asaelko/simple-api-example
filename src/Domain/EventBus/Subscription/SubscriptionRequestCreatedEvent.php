<?php

namespace App\Domain\EventBus\Subscription;

use App\Entity\SubscriptionRequest;
use CarlBundle\EventBus\BusEventInterface;

/**
 * Событие создания новой заявки на подписку
 */
class SubscriptionRequestCreatedEvent implements BusEventInterface
{
    public int $requestId;

    public function __construct(SubscriptionRequest $request)
    {
        if (!$request->getId()) {
            return;
        }

        $this->requestId = $request->getId();
    }
}
