<?php

namespace App\Domain\Core\Subscription\Controller\Admin\Factory;

use App\Domain\Core\Subscription\Controller\Admin\Request\CreateSubscriptionStockModelRequest;
use App\Entity\SubscriptionModel;
use App\Entity\SubscriptionPartner;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Exception\InvalidValueException;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionStockFactory
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function fillStockModel(SubscriptionModel $stockModel, CreateSubscriptionStockModelRequest $request): SubscriptionModel
    {
        /** @var Model $model */
        $model = $this->entityManager->getRepository(Model::class)->find($request->modelId);
        if (!$model) {
            throw new InvalidValueException('Модель не найдена');
        }

        $partner = $this->entityManager->getRepository(SubscriptionPartner::class)->find($request->partnerId);
        if (!$partner) {
            throw new InvalidValueException('Партнер не найден');
        }

        $stockModel->setModel($model)
            ->setPartner($partner)
            ->setPartnerCode($request->parnerCode)
            ->setPrice($request->price)
            ->setOptions($request->options)
            ->setDescription($request->description)
            ->setEquipmentUrl($request->eqipmentUrl);

        return $stockModel;
    }
}
