<?php

namespace App\Domain\WebSite\Catalog\Controller;

use App\Domain\Core\Brand\Repository\BrandRepository;
use App\Domain\Core\Model\Repository\ModelRepository;
use CarlBundle\Entity\Brand;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BrandController extends AbstractController
{
    private BrandRepository $brandRepository;
    private ModelRepository $modelRepository;

    public function __construct(
        BrandRepository $brandRepository,
        ModelRepository $modelRepository
    )
    {
        $this->brandRepository = $brandRepository;
        $this->modelRepository = $modelRepository;
    }

    /**
     * Получение списка брендов и их моделей для веб-сайта
     *
     * @OA\Get(
     *     operationId="/web/catalog/brands/list"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список брендов и их моделей",
     *     @OA\JsonContent(
     *              type="array",
     *              @OA\Items(type="object", properties={
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="logo", type="string"),
     *                  @OA\Property(property="models", type="array", @OA\Items(type="integer"))
     *              })
     *     )
     * )
     *
     * @return JsonResponse
     * @OA\Tag(name="Web\Catalog")
     */
    public function listAction(): JsonResponse
    {
        $brands = $this->brandRepository->findAll();

        $models = $this->modelRepository->getRichModelsDataForBrands(array_map(static fn(Brand $brand) => $brand->getId(), $brands));

        $indexedBrands = [];
        array_walk($brands, static function (Brand $brand) use (&$indexedBrands) {
            return $indexedBrands[$brand->getId()] = $brand;
        });

        $result = [];
        foreach($models as $modelData) {
            $brandId = $modelData['brand_id'];
            $result[$brandId] ??= [
                'brand' => $indexedBrands[$brandId],
                'models' => []
            ];
            $result[$brandId]['models'][] = $modelData['id'];
        }

        return new JsonResponse(array_map(
                static fn($brandData) => [
                    'id'    => $brandData['brand']->getId(),
                    'name'  => $brandData['brand']->getName(),
                    'logo' => $brandData['brand']->getLogo() ? $brandData['brand']->getLogo()->getAbsolutePath() : null,
                    'models' => $brandData['models']
                ],
                array_values($result))
        );
    }
}