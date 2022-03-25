<?php


namespace App\Domain\Infrastructure\CarPrice\Response;


use App\Entity\CarPriceOffice;
use OpenApi\Annotations as OA;

class OfficeResponse
{
    /**
     * @OA\Property(description="Lat для отображения точки на карте")
     */
    public float $lat;

    /**
     * @@OA\Property(description="Lon для отображения точки на карте")
     */
    public float $lon;

    /**
     * @OA\Property(description="id записи в нашей системе для создания заявки")
     */
    public int $id;

    /**
     * @OA\Property(description="Адрес точки на карте")
     */
    public string $address;

    public function __construct(CarPriceOffice $office)
    {
        $this->id = $office->getId();
        $this->lat = $office->getLat();
        $this->lon = $office->getLon();
        $this->address = $office->getAddress();
    }
}