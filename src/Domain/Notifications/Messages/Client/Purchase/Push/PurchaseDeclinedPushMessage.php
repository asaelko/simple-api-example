<?php

namespace App\Domain\Notifications\Messages\Client\Purchase\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use App\Entity\Purchase\Purchase;
use CarlBundle\Entity\Client;

/**
 * Уведомление об отклонении заявки на покупку
 */
final class PurchaseDeclinedPushMessage extends AbstractPushMessage
{
    public const TITLE = 'client.purchase.declined.title';
    public const TEXT = 'client.purchase.declined.text';

    public function __construct(Purchase $purchase)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $purchase->getClient()->getFirstName() ? $purchase->getClient()->getFirstName() . ', ' : '',
        ];

        $this->receivers = [Client::class => [$purchase->getClient()->getId()]];
        $this->data = [
            'url' => sprintf('https://carl-drive.ru/buy/%d', $purchase->getId()),
        ];
    }
}
