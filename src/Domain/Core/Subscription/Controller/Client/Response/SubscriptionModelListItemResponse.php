<?php

namespace App\Domain\Core\Subscription\Controller\Client\Response;

use App\Entity\SubscriptionModel;
use OpenApi\Annotations as OA;

/**
 * Элемент списка доступных вариаций подписки для модели
 */
class SubscriptionModelListItemResponse
{
    public int $id;
    public int $price;
    public int $term = 12; // фикс на 12 месяцах
    public int $contractSum;
    public SubscriptionPartnerResponse $partner;
    public bool $requested = false;
    public ?string $equipmentUrl;

    /**
     * @OA\Property(type="object")
     */
    public array $options;

    public function __construct(SubscriptionModel $model, array $requestedIds)
    {
        $this->id = $model->getId();
        $this->price = $model->getPrice();
        $this->contractSum = $this->price * $this->term;

        $this->options = $model->getOptions();

        $this->partner = new SubscriptionPartnerResponse($model->getPartner());

        $this->requested = in_array($model->getId(), $requestedIds, true);

        $this->equipmentUrl = $model->getEquipmentUrl();
    }
}
