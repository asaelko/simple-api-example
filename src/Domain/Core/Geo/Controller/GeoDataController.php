<?php

namespace App\Domain\Core\Geo\Controller;

use App\Domain\Core\Geo\Controller\Request\AddressToGeoCodeRequest;
use App\Domain\Core\Geo\Controller\Request\GeoPointRequest;
use App\Domain\Core\Geo\Service\GeoDataService;
use CarlBundle\Entity\City;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Exception\RestException;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Контроллер для работы с геоданными
 */
class GeoDataController extends AbstractController
{
    /**
     * Получить адрес по координатам
     *
     * Метод принимает координаты точки и пытается определить для этой точки адрес
     *
     * @OA\Get(operationId="system/getAddressByGeo")
     *
     * @OA\Parameter(
     *     name="lat",
     *     in="query",
     *     description="Широта в координатах, обязательно",
     *     @OA\Schema(type="number")
     * )
     * @OA\Parameter(
     *     name="lng",
     *     in="query",
     *     description="Долгота в координатах, обязательно",
     *     @OA\Schema(type="number")
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт определенный адрес",
     *     @OA\JsonContent(
     *            @OA\Property(
     *              property="address",
     *              type="object",
     *              example="г Москва, Ленинградский пр-кт, д 37 к 3 стр 8"
     *           )
     *    )
     * )
     *
     * @OA\Response(
     *     response=404, description="Адрес не найден",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Адрес не найден")
     *     )
     * )
     *
     * @OA\Response(
     *     response=473, description="Ошибка валидации переданных данных",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Поле lat обязательно")
     *     )
     * )
     *
     * @OA\Response(
     *     response=421, description="Ошибка принадлежности адреса к региону бронирования",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Адрес выходит за пределы МКАД")
     *     )
     * )
     *
     * @OA\Tag(name="System\Geo")
     *
     * @param GeoPointRequest $request
     * @param GeoDataService  $geoDataService
     *
     * @return JsonResponse
     * @throws RestException
     */
    public function getAddressByGeo(GeoPointRequest $request, GeoDataService $geoDataService): JsonResponse
    {
        if (!$request->lat || !$request->lon) {
            throw new InvalidValueException('Не переданы координаты поиска');
        }
        $city = $this->getDoctrine()->getRepository(City::class)->find(City::MOSCOW_ID);
        return new JsonResponse(['address' => $geoDataService->getAddressByGeoData($city, $request->lat, $request->lon)]);
    }

    /**
     * Получить координаты по адресу
     *
     * По переданному адресу пытаемся определить его координаты
     *
     * @OA\Get(operationId="system/getGeoByAddress")
     *
     * @OA\Parameter(
     *      name="address",
     *      in="query",
     *      description="Текст адреса",
     *      @OA\Schema(type="string")
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив имен",
     *     @OA\JsonContent(
     *            @OA\Property(
     *              property="geo",
     *              type="object",
     *                  @OA\Property(
     *                      property="lat",
     *                      description="lat",
     *                      type="string",
     *                      example="43.334343"
     *                  ),
     *                  @OA\Property(
     *                      property="lon",
     *                      description="lon",
     *                      type="string",
     *                      example="23.33333"
     *                  )
     *           )
     *    )
     * )
     *
     * @OA\Response(
     *     response=473, description="Ошибка валидации переданных данных",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Поле address обязательно")
     *     )
     * )
     *
     * @OA\Response(
     *     response=421, description="Ошибка принадлежности адреса к региону бронирования",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Адрес выходит за пределы МКАД")
     *     )
     * )
     *
     * @OA\Tag(name="System\Geo")
     *
     * @param AddressToGeoCodeRequest $request
     * @param GeoDataService          $geoDataService
     *
     * @return JsonResponse
     * @throws RestException
     */
    public function getGeoByAddress(AddressToGeoCodeRequest $request, GeoDataService $geoDataService): JsonResponse
    {
        $city = $this->getDoctrine()->getRepository(City::class)->find(City::MOSCOW_ID);
        return new JsonResponse(['geo' => $geoDataService->getGeoByAddress($city, $request->address)]);
    }
}
