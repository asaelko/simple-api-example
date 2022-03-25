<?php

namespace App\Domain\Notifications\Messages\Client\Offer\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use DealerBundle\Entity\DriveOffer;

/**
 * Уведомление о новом оффере от дилера
 */
final class NewOfferPushMessage extends AbstractPushMessage
{
    public const TITLE = 'client.offer.new_offer.title';
    public const TEXT = 'client.offer.new_offer.text';

    public function __construct(DriveOffer $offer)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;

        $discount = '';
        if ($offer->getFormattedDiscount()) {
            $discount = sprintf(', скидка %s', $offer->getFormattedDiscount());
        }
        $model = $offer->getDealerCar()->getEquipment()->getModel();

        $this->context = [
            'dealerName' => $offer->getDealer()->getName(),
            'modelName' => $model->getNameWithBrand(),
            'price' => $offer->getFormattedPrice(),
            'discount' => $discount,
        ];

        $this->receivers = [Client::class => [$offer->getClient()->getId()]];

        $this->data = [
            'url' => sprintf('https://carl-drive.ru/commerce_offer/%d', $offer->getId())
        ];
    }
}
