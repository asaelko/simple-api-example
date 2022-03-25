<?php

namespace App\Domain\WebSite\Catalog\Controller;

use App\Domain\Core\Model\Repository\ModelRepository;
use App\Domain\Core\Subscription\Repository\SubscriptionModelRepository;
use App\Domain\WebSite\Catalog\Request\ListSubscriptionsRequest;
use App\Domain\WebSite\Catalog\Response\ModelResponse;
use App\Domain\WebSite\Catalog\Response\SubscriptionResponse;
use App\Entity\SubscriptionModel;
use CarlBundle\Entity\Model\Model;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class SubscriptionController extends AbstractController
{
    private ModelRepository $modelRepository;
    private SubscriptionModelRepository $subscriptionModelRepository;

    public function __construct(
        ModelRepository $modelRepository,
        SubscriptionModelRepository $subscriptionModelRepository
    )
    {
        $this->modelRepository = $modelRepository;
        $this->subscriptionModelRepository = $subscriptionModelRepository;
    }

    /**
     * Получение списка подписок для каталога с фильтрами
     *
     * @OA\Get(
     *     operationId="/web/catalog/subscription/list",
     *     @OA\RequestBody(
     *          @DocModel(type=ListSubscriptionsRequest::class)
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
     *              @OA\Items(ref=@DocModel(type=SubscriptionResponse::class))
     *        )
     *     )
     * )
     *
     * @OA\Tag(name="Web\Catalog")
     * @param ListSubscriptionsRequest $request
     *
     * @return JsonResponse
     */
    public function listAction(ListSubscriptionsRequest $request): JsonResponse
    {
        $resultArray = $this->subscriptionModelRepository->listCatalogForWebSite($request);
        $models = array_map(static fn(SubscriptionModel $sm) => $sm->getModel(), $resultArray['items']);

        $stockData = $this->modelRepository->getStocksDataForModel($models);
        $tagData = $this->modelRepository->getTagsForModels($models);

        $modelItems = [];
        array_walk($models, static function(Model $model) use (&$modelItems, $stockData, $tagData) {
           $modelItems[$model->getId()] = new ModelResponse($model, $stockData, $tagData);
        });

        $items = array_map(
            static fn(SubscriptionModel $sm) => new SubscriptionResponse($sm, $modelItems[$sm->getModel()->getId()]),
            $resultArray['items']
        );

        $filters = $this->subscriptionModelRepository->listFiltersForWebSite($request);

        return new JsonResponse(['items' => $items, 'count' => $resultArray['count'], 'filters' => $filters]);
    }
}