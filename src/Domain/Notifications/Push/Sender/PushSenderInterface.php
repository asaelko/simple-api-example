<?php

namespace App\Domain\Notifications\Push\Sender;

use App\Domain\Notifications\Push\PushMessageInterface;

interface PushSenderInterface
{
    public function processPush(PushMessageInterface $pushMessage): bool;
}