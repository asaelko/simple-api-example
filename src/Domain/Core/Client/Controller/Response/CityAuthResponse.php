<?php

namespace App\Domain\Core\Client\Controller\Response;

use CarlBundle\Entity\City;
use OpenApi\Annotations as OA;

/**
 * Объект города, получаемый в результате авторизации на клиентах
 */
class CityAuthResponse
{
    /**
     * Идентификатор города
     *
     * @OA\Property(type="integer", example=2)
     */
    public int $id;

    /**
     * Наименование города
     *
     * @OA\Property(type="string", nullable=true, example="Санкт-Петербург")
     */
    public string $name;

    /**
     * Широта
     *
     * @OA\Property(type="float", example=59.939095)
     */
    public ?float $lat = null;

    /**
     * Долгота
     *
     * @OA\Property(type="float", example=30.315868)
     */
    public ?float $lng = null;

    /**
     * Границы города в формате Polyline
     *
     * @OA\Property(type="string", nullable=true, example="wilmJmnuuDclA_iRutAsmTahCeeIueBogJrXsmTfbH_tHf`GsxJjhC_fBb{@ogJz_Cor@~`Gx{DtxFePlfBjhCz}AbeIzbDnnMnn@p|HqkDp|HxjB|eMfoBn`G_~ApxUwr@pjO`dAlnMsyAlrKqcF~lEswBdoQamCjoFyzGsnBqxHy{DyrBsjDse@_iR??mr@goQ")
     */
    public ?string $polyline = null;

    public function __construct(City $city)
    {
        $this->id = $city->getId();
        $this->name = $city->getName();
        $this->lat = $city->getLatitude();
        $this->lng = $city->getLongitude();
        $this->polyline = $city->getPolyline();
    }
}
