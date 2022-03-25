<?php

namespace App\Domain\WebSite\Catalog\Response;

use CarlBundle\Entity\CarPhotoDictionary;
use DealerBundle\Entity\Car;
use DealerBundle\Entity\DriveOffer;
use OpenApi\Annotations as OA;

class StockCarResponse
{
    public int $id;

    public ?string $vin = null;

    public int $price;

    public ?int $year = null;

    public int $state;

    /**
     * @var array|null
     *
     * @OA\Property(type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="hex", type="string", nullable=true)
     * ))
     */
    public ?array $carColor = null;

    /**
     * @var array
     *
     * @OA\Property(type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     * ))
     */
    public array $equipment;

    /**
     * @var array
     *
     * @OA\Property(type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="organizationName", type="string")
     * ))
     */
    public array $dealer;

    public ?int $carMileage = null;

    /**
     * @var array
     *
     * @OA\Property(type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="path", type="string"),
     * ))
     */
    public array $photos;

    public bool $bookingAvailable = false;
    public ?int $bookingPrice = null;
    public ?int $bookingPeriod = null;

    public bool $purchaseAvailable = false;
    public bool $deliveryAvailable = false;
    public bool $testDriveAvailable = false;

    public bool $loanAvailable = false;
    public bool $leasingAvailable = false;
    public bool $subscriptionAvailable = false;
    public bool $longDriveAvailable = false;

    public function __construct(Car $car, array $modelTagData = [])
    {
        $this->id = $car->getId();
        $this->vin = $car->getVin();
        $this->price = $car->getPrice();
        $this->year = $car->getPTSyear();
        $this->state = $car->getState();
        $this->carMileage = $car->getCarMileage();

        $this->bookingAvailable = $car->getDealer()->hasBookingAbility();
        if ($this->bookingAvailable) {
            $this->bookingPrice = DriveOffer::DEFAULT_BOOKING_PRICE;
            $this->bookingPeriod = DriveOffer::DEFAULT_BOOKING_PERIOD;
        }

        $this->purchaseAvailable = $car->getDealer()->hasPurchaseAbility();
        $this->deliveryAvailable = $car->getDealer()->hasDeliveryAbility();

        $this->testDriveAvailable = $car->getEquipment()->getModel()->getActiveCar(true)
            && $car->getEquipment()->getModel()->getActiveCar(true)->getFreeScheduleTime();

        $this->equipment = [
            'id'           => $car->getEquipment()->getId(),
            'name'         => $car->getEquipment()->getName(),
            'modification' => $car->getEquipment()->getSubname(),
            'acceleration' => $car->getEquipment()->getFormattedAcceleration(),
            'capacity'     => $car->getEquipment()->getFormattedCapacity(),
            'fuelType'     => $car->getEquipment()->getFormattedFuelType(),
            'power'        => $car->getEquipment()->getFormattedPower(),
            'rate'         => $car->getEquipment()->getFormattedRate(),
            'wheels'       => $car->getEquipment()->getFormattedWheels(),
            'transmission' => $car->getEquipment()->getTransmission(),
            'description'  => $car->getEquipment()->getDescription(),
            'model'        => [
                'id'          => $car->getEquipment()->getModel()->getId(),
                'name'        => $car->getEquipment()->getModel()->getName(),
                'description' => $car->getEquipment()->getModel()->getDescription(),
                'brand'       => [
                    'id'   => $car->getEquipment()->getModel()->getBrand()->getId(),
                    'name' => $car->getEquipment()->getModel()->getBrand()->getName(),
                ],
            ],
        ];

        $this->dealer = [
            'id'               => $car->getDealer()->getId(),
            'name'             => $car->getDealer()->getName(),
            'address'          => $car->getDealer()->getAddress(),
            'organizationName' => $car->getDealer()->getOrganizationName(),
        ];

        if ($car->getCarColor()) {
            $this->carColor = [
                'id'   => $car->getCarColor()->getId(),
                'hex'  => $car->getCarColor()->getHex() ?: null,
                'name' => $car->getCarColor()->getName(),
            ];
        }

        $this->photos = $car->getCarPhotoDictionaries()
            ->map(static fn(CarPhotoDictionary $dictionary) => [
                'id'   => $dictionary->getPhoto()->getId(),
                'path' => $dictionary->getPhoto()->getAbsolutePath(),
            ])
            ->toArray();
        if (!$this->photos && $car->getEquipment()->getModel()->getSitePhoto()) {
            $this->photos[] = [
                'id'   => $car->getEquipment()->getModel()->getSitePhoto()->getId(),
                'path' => $car->getEquipment()->getModel()->getSitePhoto()->getAbsolutePath(),
            ];
        }

        $modelId = $car->getEquipment()->getModel()->getId();
        if (isset($modelTagData[$modelId])) {
            $this->loanAvailable = $modelTagData[$modelId]['loan'];
            $this->leasingAvailable = $modelTagData[$modelId]['leasing'];
            $this->subscriptionAvailable = $modelTagData[$modelId]['subscription'];
            $this->longDriveAvailable = $modelTagData[$modelId]['longDrive'];
        }
    }
}
