<?php

namespace App\Domain\EventBus\Leasing;

use CarlBundle\Entity\Leasing\LeasingRequest;
use CarlBundle\EventBus\BusEventInterface;

/**
 * Создан новый запрос на лизинг
 */
class LeasingRequestCreatedEvent implements BusEventInterface
{
    /** Идентификатор созданного запроса */
    public string $leasingRequestId;

    public function __construct(LeasingRequest $request)
    {
        $this->leasingRequestId = $request->getId()->getHex();
    }
}
