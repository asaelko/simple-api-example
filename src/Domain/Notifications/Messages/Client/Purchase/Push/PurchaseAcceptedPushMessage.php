<?php

namespace App\Domain\Notifications\Messages\Client\Purchase\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use App\Entity\Purchase\Purchase;
use CarlBundle\Entity\Client;

/**
 * Пуш-уведомление о том, что покупка авто была подтверждена
 */
final class PurchaseAcceptedPushMessage extends AbstractPushMessage
{
    public const TITLE = 'client.purchase.accepted.title';
    public const TEXT = 'client.purchase.accepted.text';

    public function __construct(Purchase $purchase)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $purchase->getClient()->getFirstName() ? $purchase->getClient()->getFirstName() . ', ' : '',
            'modelName' => $purchase->getModel()->getNameWithBrand(),
        ];

        $this->receivers = [Client::class => [$purchase->getClient()->getId()]];
        $this->data = [
            'url' => sprintf('https://carl-drive.ru/buy/%d', $purchase->getId()),
        ];
    }
}
