<?php

namespace App\Domain\Core\LongDrive\Controller\Admin\Response;

use App\Entity\LongDrive\LongDriveModel;
use OpenApi\Annotations as OA;

class LongDriveAutoResponse
{
    public int $id;

    public int $modelId;

    public ?int $basebuyModelId;

    public string $modelName;

    public string $brandName;

    public ?int $modificationId;

    public ?int $equipmentId;

    public ?string $modificationName;

    public ?string $equipmentName;

    public ?int $price;

    /**
     * @OA\Property(type="object")
     */
    public array $prices = [];

    public ?string $description;

    public PartnerResponse $partner;

    public function __construct(LongDriveModel $auto)
    {
        $this->id = $auto->getId();
        $this->modelId = $auto->getModel()->getId();
        if ($auto->getModel()->getBasebuyModel()) {
            $this->basebuyModelId = $auto->getModel()->getBasebuyModel()->getId();
        }
        $this->modelName = $auto->getModel()->getName();
        $this->brandName = $auto->getModel()->getBrand()->getName();

        if ($auto->getModification()) {
            $this->modificationId = $auto->getModification()->getId();
            $this->modificationName = $auto->getModification()->getName();
        }

        if ($auto->getEquipment()) {
            $this->equipmentId = $auto->getEquipment()->getId();
            $this->equipmentName = $auto->getEquipment()->getName();
        }

        $this->price = $auto->getPrice();
        $this->prices = $auto->getPrices();
        $this->description = $auto->getDescription();

        $this->partner = new PartnerResponse($auto->getPartner());
    }
}