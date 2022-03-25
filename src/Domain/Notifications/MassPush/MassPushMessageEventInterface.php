<?php

namespace App\Domain\Notifications\MassPush;

use App\Domain\Notifications\NotificationInterface;

interface MassPushMessageEventInterface extends NotificationInterface
{
    /**
     * Получает ID масспуша, который необходимо разослать
     *
     * @return int
     */
    public function getMassPushNotificationId(): int;
}
