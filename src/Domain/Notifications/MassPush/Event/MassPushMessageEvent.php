<?php

namespace App\Domain\Notifications\MassPush\Event;

use App\Domain\Notifications\MassPush\MassPushMessageEventInterface;
use App\Entity\Notifications\MassPush\MassPushNotification;

/**
 * Событие отправки масспуша
 */
class MassPushMessageEvent implements MassPushMessageEventInterface
{
    private int $massPushNotificationId;

    public function __construct(MassPushNotification $massPushNotification)
    {
        $this->massPushNotificationId = $massPushNotification->getId();
    }

    /**
     * @return int
     */
    public function getMassPushNotificationId(): int
    {
        return $this->massPushNotificationId;
    }
}
