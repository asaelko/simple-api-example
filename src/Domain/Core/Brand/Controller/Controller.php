<?php

namespace App\Domain\Core\Brand\Controller;

use App\Domain\Core\Brand\Controller\Request\BrandFilterRequest;
use App\Domain\Core\Brand\Controller\Response\Client\ClientBrandResponse;
use App\Domain\Core\Brand\Service\BrandService;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Контроллер брендов
 */
class Controller extends AbstractController
{
    private BrandService $brandService;

    public function __construct(
        BrandService $brandService
    )
    {
        $this->brandService = $brandService;
    }

    /**
     * Получить все бренды
     *
     * @OA\Get(operationId="brand/list")
     *
     * @OA\Response(
     *     response=200,
     *     description="Бренды, доступные пользователю",
     *     @OA\JsonContent(
     *          @OA\Property(type="array", @OA\Items(ref=@DocModel(type=ClientBrandResponse::class)))
     *     )
     * )
     *
     * @OA\Tag(name="System\Brands")
     *
     * @param BrandFilterRequest $filterRequest
     * @return JsonResponse
     */
    public function getList(BrandFilterRequest $filterRequest): JsonResponse
    {
        return new JsonResponse($this->brandService->getList($filterRequest));
    }

    /**
     * Получить короткий список брендов и моделей
     *
     * Удобен, например, для реализации дропдаунов
     *
     * @OA\Get(operationId="brand/short/list")
     *
     * @OA\Tag(name="System\Brands")
     *
     * @return JsonResponse
     */
    public function getShortList(): JsonResponse
    {
        return new JsonResponse();
    }

    /**
     * Получаем бренд по его ID
     *
     * @OA\Get(operationId="brand/get")
     *
     * @OA\Response(
     *     response=200,
     *     description="Бренды, доступные пользователю",
     *     @DocModel(type=ClientBrandResponse::class)
     * )
     *
     * @OA\Tag(name="System\Brands")
     *
     * @param int $brandId
     *
     * @return JsonResponse
     */
    public function getBrand(int $brandId): JsonResponse
    {
        return new JsonResponse($this->brandService->get($brandId));
    }
}
