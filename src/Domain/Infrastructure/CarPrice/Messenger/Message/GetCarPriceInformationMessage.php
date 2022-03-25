<?php


namespace App\Domain\Infrastructure\CarPrice\Messenger\Message;


use CarlBundle\Entity\ClientCar;

class GetCarPriceInformationMessage
{
    private int $carId;

    public function __construct(ClientCar $car)
    {
        $this->carId = $car->getId();
    }

    public function getCarId(): int
    {
        return $this->carId;
    }
}