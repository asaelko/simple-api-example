<?php

namespace App\Domain\Core\Brand\Controller;

use App\Domain\Core\Brand\Controller\Request\AdminBrandDataRequest;
use App\Domain\Core\Brand\Controller\Request\BrandFilterRequest;
use App\Domain\Core\Brand\Controller\Response\BaseBrandResponse;
use App\Domain\Core\Brand\Service\AdminBrandService;
use CarlBundle\Entity\Brand;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Exception\RestException;
use CarlBundle\Response\Common\BooleanResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Контроллер брендов для администраторов
 */
class AdminController extends AbstractController
{
    private AdminBrandService $brandService;

    public function __construct(
        AdminBrandService $brandService
    )
    {
        $this->brandService = $brandService;
    }

    /**
     * Получить все бренды
     *
     * @OA\Get(
     *     operationId="admin/brand/list",
     *     @OA\Parameter(
     *          name="hasActivity",
     *          in="query",
     *          description="Если true, вернет только те бренды, у которых были поездки",
     *          @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *          name="filters",
     *          in="query",
     *          description="Набор фильтров",
     *          @OA\Schema(type="object")
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Brand")
     *
     * @param BrandFilterRequest $filterRequest $Request
     *
     * @return JsonResponse
     */
    public function list(BrandFilterRequest $filterRequest): JsonResponse
    {
        $brands = $this->brandService->getList($filterRequest);

        return new JsonResponse(array_map(
            static fn(Brand $brand) => new BaseBrandResponse($brand),
            $brands
        ));
    }

    /**
     * Получить бренд по ID
     *
     * @OA\Get(
     *     operationId="admin/brand"
     * )
     *
     * @OA\Tag(name="Admin\Brand")
     *
     * @param int $brandId
     *
     * @return JsonResponse
     */
    public function getBrand(int $brandId): JsonResponse
    {
        $brand = $this->brandService->resolveBrand($brandId);

        return new JsonResponse(new BaseBrandResponse($brand));
    }

    /**
     * Создание нового бренда
     *
     * @OA\Post(
     *     operationId="admin/brand/create",
     *     @OA\RequestBody(
     *          @Model(type=AdminBrandDataRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=461,
     *     description="Ошибка валидации бренда",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="error",
     *          type="string",
     *          example="Бренд с таким именем уже существует"
     *        )
     *     )
     * )
     * @OA\Tag(name="Admin\Brand")
     *
     * @param AdminBrandDataRequest $brandRequest
     * @return JsonResponse
     * @throws InvalidValueException
     */
    public function create(AdminBrandDataRequest $brandRequest): JsonResponse
    {
        $brand = $this->brandService->create($brandRequest);

        return new JsonResponse(new BaseBrandResponse($brand));
    }

    /**
     * Обновление бренда
     *
     * @OA\Post(
     *     operationId="admin/brand/update",
     *     @OA\RequestBody(
     *          @Model(type=AdminBrandDataRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=461,
     *     description="Ошибка валидации бренда",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="error",
     *          type="string",
     *          example="Бренд с таким именем уже существует"
     *        )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Brand")
     *
     * @param AdminBrandDataRequest $brandRequest
     * @param int $brandId
     * @return JsonResponse
     * @throws InvalidValueException
     */
    public function update(AdminBrandDataRequest $brandRequest, int $brandId): JsonResponse
    {
        $brand = $this->brandService->update($brandId, $brandRequest);

        return new JsonResponse(new BaseBrandResponse($brand));
    }

    /**
     * Удаление бренда
     *
     * @OA\Delete(
     *     operationId="admin/brand/delete"
     * )
     *
     * @OA\Tag(name="Admin\Brand")
     *
     * @param int $brandId
     *
     * @return JsonResponse
     * @throws RestException
     */
    public function delete(int $brandId): JsonResponse
    {
        $this->brandService->delete($brandId);

        return new JsonResponse(new BooleanResponse(true));
    }
}
