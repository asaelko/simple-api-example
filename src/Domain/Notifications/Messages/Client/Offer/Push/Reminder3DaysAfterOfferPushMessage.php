<?php

namespace App\Domain\Notifications\Messages\Client\Offer\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use DateTime;
use DealerBundle\Entity\DriveOffer;
use RuntimeException;

/**
 * Уведомляем клиента о забытом оффере через три дня после ответа дилером на оффер
 */
final class Reminder3DaysAfterOfferPushMessage extends AbstractPushMessage
{
    public const TITLE = 'client.offer.three_days_after_reminder.title';
    public const TEXT = 'client.offer.three_days_after_reminder.text';

    public function __construct(DriveOffer $offer)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;

        $car = $offer->getDealerCar();
        $expirationDate = $offer->getExpirationAt();
        if (!$expirationDate || !$car || !$car->getEquipment()) {
            throw new RuntimeException('Недостаточно данных для отправки пуша-напоминания об оффере');
        }

        $expirationDaysCount = $expirationDate->diff(new DateTime());
        $expirationDaysCount = $expirationDaysCount->days;

        $this->context = [
            'clientName' => $offer->getClient()->getFirstName() ? $offer->getClient()->getFirstName() . ', ' : '',
            'dealerName' => $offer->getDealer()->getName(),
            'price' => $offer->getFormattedPrice(),
            'days' => $expirationDaysCount,
        ];

        $this->receivers = [Client::class => [$offer->getClient()->getId()]];

        $this->data = [
            'url' => sprintf('https://carl-drive.ru/commerce_offer/%d', $offer->getId()),
        ];
    }
}
