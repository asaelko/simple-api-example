<?php

namespace App\Domain\WebSite\News\Controller;

use App\Domain\WebSite\News\Request\ListNewsRequest;
use App\Domain\WebSite\News\Response\NewsResponse;
use App\Entity\News;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ClientNewsController extends AbstractController
{
    /**
     * @OA\Get(
     *     operationId="/web/news/list"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список новостей",
     *     @OA\JsonContent(
     *        @OA\Property(
     *              property="count",
     *              type="integer",
     *              example=10
     *        ),
     *        @OA\Property(
     *              property="items",
     *              type="array",
     *              @OA\Items(ref=@DocModel(type=NewsResponse::class))
     *        )
     *     )
     * )
     * @OA\Parameter(
     *  name="limit",
     *  in="query",
     *  required=true,
     *  description="limit",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *  name="offset",
     *  in="query",
     *  required=true,
     *  description="offset",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Web\Client\News")
     * @param ListNewsRequest $request
     * @return JsonResponse
     */
    public function list(ListNewsRequest $request): JsonResponse
    {
        $result = $this->getDoctrine()->getRepository(News::class)->list($request->limit, $request->offset);

        $response = array_map(
            function (News $news)
            {
                return new NewsResponse($news);
            },
            $result['items']
        );

        return new JsonResponse(['items' => $response, 'count' => $result['count']]);
    }

    /**
     * Получить список новостей для бренда
     *
     * @OA\Get(
     *     operationId="/web/brand/news/list"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список новостей",
     *     @OA\JsonContent(
     *        @OA\Property(
     *              property="count",
     *              type="integer",
     *              example=10
     *        ),
     *        @OA\Property(
     *              property="items",
     *              type="array",
     *              @OA\Items(ref=@DocModel(type=NewsResponse::class))
     *        )
     *     )
     * )
     * @OA\Parameter(
     *  name="limit",
     *  in="query",
     *  required=true,
     *  description="limit",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *  name="offset",
     *  in="query",
     *  required=true,
     *  description="offset",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Web\Client\News")
     *
     * @param ListNewsRequest $request
     * @param int             $brandId
     *
     * @return JsonResponse
     */
    public function listByBrand(ListNewsRequest $request, int $brandId): JsonResponse
    {
        $result = $this->getDoctrine()->getRepository(News::class)->list($request->limit, $request->offset, false, $brandId);

        $response = array_map(
            function (News $news)
            {
                return new NewsResponse($news);
            },
            $result['items']
        );

        return new JsonResponse(['items' => $response, 'count' => $result['count']]);
    }

    /**
     * Получить список новостей для модели
     *
     * @OA\Get(
     *     operationId="/web/model/news/list"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список новостей",
     *     @OA\JsonContent(
     *        @OA\Property(
     *              property="count",
     *              type="integer",
     *              example=10
     *        ),
     *        @OA\Property(
     *              property="items",
     *              type="array",
     *              @OA\Items(ref=@DocModel(type=NewsResponse::class))
     *        )
     *     )
     * )
     * @OA\Parameter(
     *  name="limit",
     *  in="query",
     *  required=true,
     *  description="limit",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *  name="offset",
     *  in="query",
     *  required=true,
     *  description="offset",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Web\Client\News")
     *
     * @param ListNewsRequest $request
     * @param int             $modelId
     *
     * @return JsonResponse
     */
    public function listByModel(ListNewsRequest $request, int $modelId): JsonResponse
    {
        $result = $this->getDoctrine()->getRepository(News::class)->list($request->limit, $request->offset, false, null, $modelId);

        $response = array_map(
            function (News $news)
            {
                return new NewsResponse($news);
            },
            $result['items']
        );

        return new JsonResponse(['items' => $response, 'count' => $result['count']]);
    }
}