<?php

namespace App\Messenger\Handlers;

use App\Messenger\Messages\FeedbackStatusEvent;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Обрабатывает переход поездки в статус "Получение фидбека"
 */
class FeedbackStatusEventHandler implements MessageHandlerInterface
{
    public function __construct()
    {}

    public function __invoke(FeedbackStatusEvent $testDriveMessage): void
    {}
}
