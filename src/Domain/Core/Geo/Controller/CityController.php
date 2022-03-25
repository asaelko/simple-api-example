<?php

namespace App\Domain\Core\Geo\Controller;

use App\Domain\Core\Geo\Controller\Request\GeoPointRequest;
use App\Domain\Core\Geo\Controller\Response\CityResponse;
use App\Domain\Core\Geo\Service\GeoDataService;
use CarlBundle\Entity\City;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CityController extends AbstractController
{
    private GeoDataService $geoDataService;

    public function __construct(
        GeoDataService $geoDataService
    )
    {
        $this->geoDataService = $geoDataService;
    }

    /**
     * Получить список городов для пользователя
     *
     * Метод может принимать координаты точки для сортировки списка городов по ближайшим
     *
     * @OA\Get(operationId="system/geo/cities/list")
     *
     * @OA\Parameter(
     *     name="lat",
     *     in="query",
     *     description="Широта в координатах, опционально",
     *     @OA\Schema(type="number")
     * )
     * @OA\Parameter(
     *     name="lng",
     *     in="query",
     *     description="Долгота в координатах, опционально",
     *     @OA\Schema(type="number")
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт список городов",
     *     @OA\JsonContent(
     *            ref=@Model(type=CityResponse::class)
     *    )
     * )
     *
     * @OA\Tag(name="System\Geo")
     *
     * @param GeoPointRequest $geoRequest
     *
     * @return JsonResponse
     */
    public function getCities(Request $request, GeoPointRequest $geoRequest): JsonResponse
    {
        $lat = $geoRequest->lat;
        $lng = $geoRequest->lon;

        if (!$lat || !$lng) {
            $ip = $request->headers->get('x-forwarded-for') ?? $request->server->get('REMOTE_ADDR');
            if ($ip && $ipData = $this->geoDataService->getIpCityData($ip)) {
                $lat = $ipData->location->latitude;
                $lng = $ipData->location->longitude;
            }
        }

        $cities = $this->getDoctrine()->getRepository(City::class)->getListByDistance($lat, $lng);
        $cities = array_map(static fn(City $city) => new CityResponse($city), $cities);
        $cities[] = new CityResponse($this->geoDataService->getDefaultCity());

        return new JsonResponse($cities);
    }
}