<?php

namespace App\Domain\WebSite\Catalog\Controller;

use App\Domain\Core\LongDrive\Repository\LongDriveModelRepository;
use App\Domain\Core\Model\Repository\ModelRepository;
use App\Domain\WebSite\Catalog\Request\ListLongDrivesRequest;
use App\Domain\WebSite\Catalog\Response\LongDriveResponse;
use App\Domain\WebSite\Catalog\Response\ModelResponse;
use App\Entity\LongDrive\LongDriveModel;
use CarlBundle\Entity\Model\Model;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class LongDriveController extends AbstractController
{
    private ModelRepository $modelRepository;
    private LongDriveModelRepository $longDriveModelRepository;

    public function __construct(
        ModelRepository $modelRepository,
        LongDriveModelRepository $longDriveModelRepository
    )
    {
        $this->modelRepository = $modelRepository;
        $this->longDriveModelRepository = $longDriveModelRepository;
    }

    /**
     * Получение списка лонг-драйв моделей для каталога с фильтрами
     *
     * @OA\Get(
     *     operationId="/web/catalog/long-drive/list",
     *     @OA\RequestBody(
     *          @DocModel(type=ListLongDrivesRequest::class)
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
     *              @OA\Items(ref=@DocModel(type=LongDriveResponse::class))
     *        )
     *     )
     * )
     *
     * @OA\Tag(name="Web\Catalog")
     * @param ListLongDrivesRequest $request
     *
     * @return JsonResponse
     */
    public function listAction(ListLongDrivesRequest $request): JsonResponse
    {
        $resultArray = $this->longDriveModelRepository->listCatalogForWebSite($request);
        $models = array_map(static fn(LongDriveModel $sm) => $sm->getModel(), $resultArray['items']);

        $stockData = $this->modelRepository->getStocksDataForModel($models);
        $tagData = $this->modelRepository->getTagsForModels($models);

        $modelItems = [];
        array_walk($models, static function(Model $model) use (&$modelItems, $stockData, $tagData) {
            $modelItems[$model->getId()] = new ModelResponse($model, $stockData, $tagData);
        });

        $items = array_map(
            static fn(LongDriveModel $sm) => new LongDriveResponse($sm, $modelItems[$sm->getModel()->getId()]),
            $resultArray['items']
        );

        $filters = $this->longDriveModelRepository->listFiltersForWebSite($request);

        return new JsonResponse(['items' => $items, 'count' => $resultArray['count'], 'filters' => $filters]);
    }
}