<?php

namespace App\Domain\Core\Subscription\Controller\Admin\Response;

use App\Entity\SubscriptionModel;

class SubscribeAutoResponse
{
    public int $id;

    public int $modelId;

    public string $modelName;

    public string $brandName;

    public ?string $price;

    public int $minSubscribeTerm;

    public int $maxSubscribeTerm;

    public string $description;

    public ?string $equipmentUrl;

    public function __construct(SubscriptionModel $auto)
    {
        $this->id = $auto->getId();
        $this->modelId = $auto->getModel()->getId();
        $this->modelName = $auto->getModel()->getName();
        $this->brandName = $auto->getModel()->getBrand()->getName();
        $this->price = $auto->getPrice();
        $this->minSubscribeTerm = $auto->getMinSubscribeTerm();
        $this->maxSubscribeTerm = $auto->getMaxSubscribeTerm();
        $this->description = $auto->getDescription();
        $this->equipmentUrl = $auto->getEquipmentUrl();
    }
}