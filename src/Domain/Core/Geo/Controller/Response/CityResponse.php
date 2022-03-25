<?php

namespace App\Domain\Core\Geo\Controller\Response;

use CarlBundle\Entity\City;

class CityResponse
{
    /**
     * Идентификатор города
     */
    public int $id;

    /**
     * Наименование города
     */
    public string $name;

    /**
     * Широта центра города
     */
    public ?float $latitude;

    /**
     * Долгота центра города
     */
    public ?float $longitude;

    /**
     * Область работы сервиса в городе в формате Google Polyline
     */
    public ?string $polyline;

    public function __construct(
        City $city
    )
    {
        $this->id = $city->getId();
        $this->name = $city->getName();
        $this->latitude = $city->getLatitude();
        $this->longitude = $city->getLongitude();
        $this->polyline = $city->getPolyline();
    }
}