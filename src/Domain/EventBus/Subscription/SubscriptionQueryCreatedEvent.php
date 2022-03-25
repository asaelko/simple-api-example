<?php

namespace App\Domain\EventBus\Subscription;

use App\Entity\Subscription\SubscriptionQuery;
use CarlBundle\EventBus\BusEventInterface;

/**
 * Событие создания новой заявки на подписку
 */
class SubscriptionQueryCreatedEvent implements BusEventInterface
{
    public int $requestId;

    public function __construct(SubscriptionQuery $request)
    {
        if (!$request->getId()) {
            return;
        }

        $this->requestId = $request->getId();
    }
}
