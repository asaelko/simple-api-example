<?php

namespace App\Domain\WebSite\Catalog\Response;

use App\Domain\Core\Model\Controller\Response\RateResponse;
use DealerBundle\Entity\Car;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class StockCarWithDriveDataResponse extends StockCarResponse
{
    /**
     * @var array|null
     *
     * @OA\Property(type="object", properties={
     *     @OA\Property(property="carId", type="integer"),
     *     @OA\Property(property="rate", type="object", ref=@Model(type=RateResponse::class)),
     *     @OA\Property(property="target", type="array", @OA\Items(type="string"))
     * })
     */
    public ?array $testDrive = null;

    /**
     * @var array
     *
     * @OA\Property(type="object", properties={
     *     @OA\Property(property="volume", type="integer")
     * })
     */
    public array $fuelCard = [];

    public function __construct(Car $car, array $modelTagData = [])
    {
        parent::__construct($car, $modelTagData);

        $model = $car->getEquipment()->getModel();
        $testDriveCar = $model->getActiveCar(true);
        if ($testDriveCar) {
            $this->testDrive = [
                'carId' => $testDriveCar->getId(),
                'rate' => $testDriveCar->hasTarget() && $testDriveCar->getTargetDriveRate()
                ? new RateResponse($testDriveCar->getTargetDriveRate())
                : new RateResponse($testDriveCar->getDriveRate())
            ];
            if ($testDriveCar->getCarTarget() && $testDriveCar->getCarTarget()->getCheckboxDescription()) {
                $this->testDrive['target'] = $testDriveCar->getCarTarget()->getCheckboxDescription();
            }
        }

        $this->fuelCard = [
            'volume' => $car->getFreeFuel(),
        ];
    }
}
