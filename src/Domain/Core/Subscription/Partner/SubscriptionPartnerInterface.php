<?php

namespace App\Domain\Core\Subscription\Partner;

use App\Entity\SubscriptionRequest;

interface SubscriptionPartnerInterface
{
    /**
     * Загружаем данные по условиям подписки от партнера
     */
    public function loadData(): void;

    /**
     * Передаем лид заявки партнеру
     *
     * @param SubscriptionRequest $request
     */
    public function sendLead(SubscriptionRequest $request): void;
}
