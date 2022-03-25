<?php

namespace App\Domain\WebSite\Catalog\Response;

use App\Entity\LongDrive\LongDriveModel;
use CarlBundle\Entity\Basebuy\OptionValue;
use OpenApi\Annotations as OA;

class LongDriveResponse
{
    public int $id;

    public int $price;

    public ModelResponse $model;

    /**
     * @OA\Property(type="object")
     */
    public array $prices;

    /**
     * @OA\Property(type="array", @OA\Items(type="string"))
     */
    public array $options = [];

    /**
     * @OA\Property(type="object")
     */
    public array $characteristics = [];

    public ?string $equipment = null;

    public ?string $modification = null;

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

    public function __construct(LongDriveModel $ldm, ModelResponse $model)
    {
        $this->model = $model;

        $this->id = $ldm->getId();
        $this->price = $ldm->getPrice();
        $this->prices = $ldm->getPrices();
        $this->partner = [
            'name' => $ldm->getPartner()->getName(),
            'organization' => $ldm->getPartner()->getFullOrganizationName(),
            'logo' => $ldm->getPartner()->getLogo()->getAbsolutePath()
        ];

        if ($ldm->getEquipment()) {
            $this->equipment = $ldm->getEquipment()->getName();

            $this->options = array_map(
                static fn (OptionValue $optionValue) => $optionValue->getOption()->getName(),
                $ldm->getEquipment()->getOptionValues()->toArray()
            );
        }
        if ($ldm->getModification()) {
            $this->modification = $ldm->getModification()->getName();
            foreach ($ldm->getModification()->getCharacteristicValues() as $name => $characteristicValue) {
                $this->characteristics[$name] = trim($characteristicValue->getValue() . ' ' . $characteristicValue->getUnit());
            }
        }
    }
}
