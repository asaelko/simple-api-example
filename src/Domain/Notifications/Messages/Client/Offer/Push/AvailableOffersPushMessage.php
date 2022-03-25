<?php

namespace App\Domain\Notifications\Messages\Client\Offer\Push;

use App\Domain\Notifications\Push\AbstractBuildablePushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use DealerBundle\Entity\Car as DealerCar;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;

/**
 * Пуш-уведомление клиенту о наличии стоков для запроса оффера после ТД
 */
final class AvailableOffersPushMessage extends AbstractBuildablePushMessage
{
    private const TITLE = 'client.offer.available_offers.title';
    private const TEXT = 'client.offer.available_offers.text';

    private int $driveId;

    public function __construct(Drive $drive)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;

        $driveId = $drive->getId();
        if (!$driveId) {
            throw new RuntimeException('Событие пуша по поездке без id');
        }
        $this->driveId = $driveId;

        $this->receivers = [Client::class => [$drive->getClient()->getId()]];
        $this->data = [];
        $this->context = [
            'clientName' => $drive->getClient()->getFirstName() ? $drive->getClient()->getFirstName() . ', ' : '',
        ];
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function build(EntityManagerInterface $entityManager): void
    {
        $drive = $entityManager->getRepository(Drive::class)->find($this->driveId);

        $model = $drive->getCar()->getEquipment()->getModel();

        $offersCount = $entityManager->getRepository(DealerCar::class)
            ->getModelOffersAndDealersCount($model);

        if (!$offersCount->getOffers()) {
            throw new Exception('Не удалось подобрать офферы для отправки');
        }

        $this->context['offers'] = $offersCount->getOffers();
        $this->context['modelName'] = $model->getNameWithBrand();
        $this->context['dc_count'] = $offersCount->getDealers();
    }
}
