<?php

namespace App\Domain\WebSite\Catalog\Controller;

use App\Domain\Core\Model\Repository\ModelRepository;
use App\Domain\WebSite\Catalog\Request\ListModelsRequest;
use App\Domain\WebSite\Catalog\Response\ModelResponse;
use App\Domain\WebSite\Catalog\Response\RichModelResponse;
use App\Domain\WebSite\Catalog\Service\GalleryService;
use CarlBundle\Entity\Model\Model;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModelController extends AbstractController
{
    private ModelRepository $repository;
    private GalleryService $galleryService;

    public function __construct(
        ModelRepository $repository,
        GalleryService  $galleryService
    )
    {
        $this->repository = $repository;
        $this->galleryService = $galleryService;
    }

    /**
     * Получение списка моделей для каталога с фильтрами
     *
     * @OA\Get(
     *     operationId="/web/catalog/model/list",
     *     @OA\RequestBody(
     *          @DocModel(type=ListModelsRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список моделей с учетом фильров",
     *     @OA\JsonContent(
     *        @OA\Property(
     *              property="count",
     *              type="integer",
     *              example=10
     *        ),
     *        @OA\Property(
     *              property="filters",
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(
     *                      property="type",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string"),
     *                          @OA\Property(property="photo", type="string"),
     *                          @OA\Property(property="active", type="boolean", example=true),
     *                          @OA\Property(property="selected", type="boolean", example=false)
     *                      )
     *                  )
     *              )
     *        ),
     *        @OA\Property(
     *              property="items",
     *              type="array",
     *              @OA\Items(ref=@DocModel(type=ModelResponse::class))
     *        )
     *     )
     * )
     *
     * @OA\Tag(name="Web\Catalog")
     * @param ListModelsRequest $request
     *
     * @return JsonResponse
     */
    public function listAction(ListModelsRequest $request): JsonResponse
    {
        $resultArray = $this->repository->listCatalogForWebSite($request);

        $stockData = $this->repository->getStocksDataForModel($resultArray['items']);
        $tagData = $this->repository->getTagsForModels($resultArray['items']);
        $items = array_map(
            static function (Model $model) use ($stockData, $tagData) {
                return new ModelResponse($model, $stockData, $tagData);
            },
            $resultArray['items']
        );

        $filters = $this->repository->listFiltersForWebSite($request);

        return new JsonResponse(['items' => $items, 'count' => $resultArray['count'], 'filters' => $filters]);
    }

    /**
     * Получение информации о модели для веб-сайта
     *
     * @OA\Get(
     *     operationId="/web/catalog/model/show"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Расширенный объект модели",
     *     @OA\JsonContent(
     *          @OA\Property(type="object", ref=@DocModel(type=RichModelResponse::class))
     *     )
     * )
     *
     * @param int $modelId
     *
     * @return JsonResponse
     *
     * @OA\Tag(name="Web\Catalog")
     */
    public function showAction(int $modelId): JsonResponse
    {
        $model = $this->repository->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        $stockData = $this->repository->getStocksDataForModel([$model]);
        $tagData = $this->repository->getTagsForModels([$model]);


        return new JsonResponse(new RichModelResponse(
            $model,
            $stockData[$model->getId()] ?? [],
            $tagData[$model->getId()] ?? []
        ));
    }

    /**
     * Получение галереи медиа для модели
     *
     * @OA\Get(
     *     operationId="/web/catalog/model/gallery"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Дерево галереи со вложенными списками"
     * )
     *
     * @param int $modelId
     *
     * @return array
     *
     * @OA\Tag(name="Web\Catalog")
     */
    public function showGalleryAction(int $modelId): array
    {
        $model = $this->repository->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        return $this->galleryService->getGallery($model);
    }

    /**
     * Получение категорий медиа галереи
     *
     * @OA\Get(
     *     operationId="/web/catalog/model/gallery/categories"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Массив категорий"
     * )
     *
     * @return array
     *
     * @OA\Tag(name="Web\Catalog")
     */
    public function showGalleryCategoriesAction(): array
    {
        return $this->galleryService->getGalleryCategories();
    }

    /**
     * Получение списка моделей бренда для веб-сайта
     *
     * @OA\Get(
     *     operationId="/web/catalog/brand/models"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список моделей бренда",
     *     @OA\JsonContent(
     *        @OA\Property(
     *              type="array",
     *              @OA\Items(type="object", properties={
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="photo", type="string")
     *              })
     *        )
     *     )
     * )
     *
     * @param int $brandId
     *
     * @return JsonResponse
     * @OA\Tag(name="Web\Catalog")
     */
    public function listByBrandAction(int $brandId): JsonResponse
    {
        $models = $this->repository->getRichModelsDataForBrands([$brandId]);

        $modelsIds = array_column($models, 'id');

        $models = $this->repository->findBy(['id' => $modelsIds]);

        return new JsonResponse(array_map(
                static fn(Model $model) => [
                    'id'    => $model->getId(),
                    'name'  => $model->getName(),
                    'photo' => $model->getSitePhoto() ? $model->getSitePhoto()->getAbsolutePath() : null,
                ],
                $models)
        );
    }

    /**
     * Получение списка похожих по цене моделей для веб-сайта
     *
     * @OA\Get(
     *     operationId="/web/catalog/model/similar"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список моделей",
     *     @OA\JsonContent(
     *        @OA\Property(
     *              type="array",
     *              @OA\Items(type="object", properties={
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="photo", type="string")
     *              })
     *        )
     *     )
     * )
     *
     * @param int $modelId
     *
     * @return JsonResponse
     * @OA\Tag(name="Web\Catalog")
     */
    public function listSimilarAction(int $modelId): JsonResponse
    {
        $model = $this->repository->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        $similarModels = $this->repository->searchAnalogsForModel($model, 0.1);

        return new JsonResponse(array_map(
                static fn(Model $model) => [
                    'id'      => $model->getId(),
                    'brandId' => $model->getBrand()->getId(),
                    'name'    => $model->getNameWithBrand(),
                    'photo'   => $model->getSitePhoto() ? $model->getSitePhoto()->getAbsolutePath() : null,
                ],
                $similarModels)
        );
    }
}