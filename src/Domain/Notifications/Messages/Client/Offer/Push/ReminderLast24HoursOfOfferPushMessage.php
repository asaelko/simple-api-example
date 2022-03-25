<?php

namespace App\Domain\Notifications\Messages\Client\Offer\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use DealerBundle\Entity\DriveOffer;
use RuntimeException;

/**
 * Уведомление за 24 часа до конца срока действия оффера клиенту
 */
final class ReminderLast24HoursOfOfferPushMessage extends AbstractPushMessage
{
    public const TITLE = 'client.offer.one_day_before_reminder.title';
    public const TEXT = 'client.offer.one_day_before_reminder.text';

    public function __construct(DriveOffer $offer)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;

        $car = $offer->getDealerCar();
        if (!$car || !$car->getEquipment()) {
            throw new RuntimeException('Недостаточно данных для отправки пуша-напоминания об оффере');
        }

        $this->context = [
            'clientName' => $offer->getClient()->getFirstName() ? $offer->getClient()->getFirstName() . ', ' : '',
            'dealerName' => $offer->getDealer()->getName(),
            'modelName'  => $car->getEquipment()->getModel()->getNameWithBrand(),
            'price'      => $offer->getFormattedPrice(),
        ];

        $this->receivers = [Client::class => [$offer->getClient()->getId()]];

        $this->data = [
            'url' => sprintf('https://carl-drive.ru/commerce_offer/%d', $offer->getId()),
        ];
    }
}
