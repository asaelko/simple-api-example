<?php

namespace App\Domain\Core\Suggest\Controller;

use App\Domain\Core\Suggest\Controller\Request\SuggestAddressRequest;
use App\Domain\Core\Suggest\Controller\Request\SuggestNameRequest;
use App\Service\Suggest\SuggestService;
use CarlBundle\Entity\City;
use CarlBundle\Entity\Client;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Контроллер системных саджестов
 */
class SuggestController extends AbstractController
{
    /**
     * Получить подсказки по именам
     *
     * Вернет массив имен по переданному префиксу. Префикс должен быть длиной **минимум 1 символ**
     *
     * @OA\Get(
     *     operationId="system/suggest/name",
     *     @OA\RequestBody(
     *          @OA\Parameter(
     *               name="name",
     *               in="query",
     *               description="Префикс для поиска имени",
     *               @OA\Schema(type="string", example="Вит")
     *          ),
     *          @OA\Parameter(
     *              name="count",
     *              in="query",
     *              description="Колличество записей, которое надо вернуть",
     *              @OA\Schema(type="integer")
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив имен",
     *     @OA\JsonContent(
     *            @OA\Property(
     *              property="data",
     *              type="array",
     *                  @OA\Items(
     *                      type="string",
     *                      example="Виталий"
     *                  )
     *           )
     *    )
     * )
     *
     * @OA\Response(
     *     response=473, description="Ошибка валидации переданных данных",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Поле name обязательно")
     *     )
     * )
     *
     * @OA\Tag(name="System\Suggest")
     *
     * @param SuggestNameRequest $request
     * @param SuggestService $suggestService
     * @return JsonResponse
     */
    public function suggestNamesAction(SuggestNameRequest $request, SuggestService $suggestService): JsonResponse
    {
        return new JsonResponse(['data' => $suggestService->getSuggestsForNameByPrefix($request->name, $request->count)]);
    }

    /**
     * Получить подсказки по адресам
     *
     * Вернет массив адресов по переданному префиксу
     *
     * @OA\Get(
     *     operationId="system/suggest/address",
     *     @OA\RequestBody (
     *          @OA\Parameter(
     *               name="address",
     *               in="query",
     *               description="Префикс для поиска адреса",
     *               @OA\Schema(type="string", example="Моск"),
     *               required=true
     *          ),
     *          @OA\Parameter(
     *              name="count",
     *              in="query",
     *              description="Колличество записей, которое надо вернуть",
     *              @OA\Schema(type="integer")
     *          ),
     *          @OA\Parameter(
     *              name="city",
     *              in="query",
     *              description="Id города, не обязательный параметер",
     *              @OA\Schema(type="integer")
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив адресов",
     *     @OA\JsonContent(
     *            @OA\Property(
     *              property="data",
     *              type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="value",
     *                          description="Адресс",
     *                          type="string",
     *                          example="Ленина 20"
     *                      ),
     *                      @OA\Property(
     *                          property="lat",
     *                          description="lat",
     *                          type="string",
     *                          example="33.33333"
     *                      ),
     *                      @OA\Property(
     *                          property="lon",
     *                          description="lon",
     *                          type="string",
     *                          example="22.2222"
     *                      ),
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
     * @OA\Tag(name="System\Suggest")
     *
     * @param SuggestAddressRequest $request
     * @param SuggestService $suggestService
     * @return JsonResponse
     */
    public function suggestAddressAction(SuggestAddressRequest $request, SuggestService $suggestService): JsonResponse
    {
        $user = $this->getUser();
        if ($request->city) {
            $cityFromDb = $this->getDoctrine()->getRepository(City::class)->find($request->city);
            if ($cityFromDb) {
                $city = $cityFromDb;
            }
        } elseif ($user instanceof Client and $user->getCity()) {
            $city = $user->getCity();
        } else {
            $city = $this->getDoctrine()->getRepository(City::class)->find(1);
        }
        return new JsonResponse(['data' => $suggestService->getSuggestsForAddress($request->address, $request->count, $city)]);
    }
}
