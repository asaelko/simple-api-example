<?php

namespace App\Domain\WebSite\Catalog\Response;

use App\Entity\SubscriptionModel;
use OpenApi\Annotations as OA;

class SubscriptionResponse
{
    public int $id;

    public int $price;

    public int $term = 12;

    public ModelResponse $model;

    /**
     * @OA\Property(
     *    property="partner",
     *    type="object",
     *         @OA\Property(property="organization", type="string"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="logo", type="string")
     * )
     */
    public array $partner = [];

    public ?string $equipmentUrl;

    public function __construct(SubscriptionModel $sm, ModelResponse $model)
    {
        $this->model = $model;

        $this->id = $sm->getId();
        $this->price = $sm->getPrice();
        $this->partner = [
            'name' => $sm->getPartner()->getName(),
            'organization' => $sm->getPartner()->getFullOrganizationName(),
            'logo' => $sm->getPartner()->getLogo()->getAbsolutePath()
        ];
        $this->equipmentUrl = $sm->getEquipmentUrl();
    }
}
