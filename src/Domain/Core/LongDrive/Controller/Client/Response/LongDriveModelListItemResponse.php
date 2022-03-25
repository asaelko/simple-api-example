<?php

namespace App\Domain\Core\LongDrive\Controller\Client\Response;

use App\Entity\LongDrive\LongDriveModel;
use OpenApi\Annotations as OA;

/**
 * Элемент списка доступных вариаций подписки для модели
 */
class LongDriveModelListItemResponse
{
    public int $id;

    public int $price;

    /**
     * @OA\Property(type="object")
     */
    public array $prices;

    public LongDrivePartnerResponse $partner;

    public bool $requested = false;

    /**
     * @OA\Property(type="object")
     */
    public array $options;

    /**
     * @OA\Property(type="object")
     */
    public array $equipment = [];

    /**
     * @OA\Property(type="object")
     */
    public array $modification = [];

    public function __construct(LongDriveModel $model, array $requestedIds)
    {
        $this->id = $model->getId();
        $this->price = min($model->getPrices());
        $this->prices = $model->getPrices();

        if ($model->getEquipment()) {
            $this->equipment = $model->getEquipment()->getOptionValues()->toArray();
        }
        if ($model->getModification()) {
            $this->modification = $model->getModification()->getCharacteristicValues()->toArray();
        }

        $this->options = $model->getOptions();

        $this->partner = new LongDrivePartnerResponse($model->getPartner());

        $this->requested = in_array($model->getId(), $requestedIds, true);
    }
}
