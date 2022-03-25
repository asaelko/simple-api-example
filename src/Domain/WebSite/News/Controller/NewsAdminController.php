<?php


namespace App\Domain\WebSite\News\Controller;


use App\Domain\WebSite\News\Request\CreateNewsRequest;
use App\Domain\WebSite\News\Request\ListNewsRequest;
use App\Domain\WebSite\News\Request\RemoveNewsRequest;
use App\Domain\WebSite\News\Request\UpdateNewsRequest;
use App\Domain\WebSite\News\Response\NewsResponse;
use App\Domain\WebSite\News\Service\NewsService;
use App\Entity\News;
use CarlBundle\Entity\Media\Media;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsAdminController extends AbstractController
{
    private NewsService $newsService;

    public function __construct(
        NewsService $newsService
    )
    {
        $this->newsService = $newsService;
    }

    /**
     * Создание новости
     *
     * @OA\Post(
     *     operationId="/admin/web/news/create",
     *     @OA\RequestBody(
     *          @DocModel(type=CreateNewsRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Новость",
     *     @OA\JsonContent(
     *      ref=@DocModel(type=NewsResponse::class)
     *     )
     * )
     * @OA\Tag(name="Admin\News")
     * @param CreateNewsRequest $request
     * @return JsonResponse
     */
    public function create(CreateNewsRequest $request): JsonResponse
    {
        $photo = $this->getDoctrine()->getRepository(Media::class)->find($request->photo);

        if (!$photo) {
            throw new NotFoundHttpException('Медиа с таким id не найдено');
        }

        $news = new News();
        $news = $this->newsService->fillNews($news, $photo, $request);

        $em = $this->getDoctrine()->getManager();
        $em->persist($news);
        $em->flush();

        return new JsonResponse(new NewsResponse($news));
    }

    /**
     * Обновление новости
     *
     * @OA\Post(
     *     operationId="/admin/web/news/update",
     *     @OA\RequestBody(
     *          @DocModel(type=UpdateNewsRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Новость",
     *     @OA\JsonContent(
     *      ref=@DocModel(type=NewsResponse::class)
     *     )
     * )
     * @OA\Tag(name="Admin\News")
     * @param UpdateNewsRequest $request
     * @return JsonResponse
     */
    public function update(UpdateNewsRequest $request): JsonResponse
    {
        $news = $this->getDoctrine()->getRepository(News::class)->find($request->id);

        if (!$news) {
            throw new NotFoundHttpException('Новость с таким id не найдено');
        }

        $photo = $this->getDoctrine()->getRepository(Media::class)->find($request->photo);

        if (!$photo) {
            throw new NotFoundHttpException('Фото с таким id не найдено');
        }

        $news = $this->newsService->fillNews($news, $photo, $request);

        $em = $this->getDoctrine()->getManager();
        $em->persist($news);
        $em->flush();

        return new JsonResponse(new NewsResponse($news));
    }

    /**
     * @OA\Get(
     *     operationId="/admin/web/news/list"
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
     * @OA\Tag(name="Admin\News")
     * @param ListNewsRequest $request
     * @return JsonResponse
     */
    public function list(ListNewsRequest $request): JsonResponse
    {
        $result = $this->getDoctrine()->getRepository(News::class)->list($request->limit, $request->offset, true);

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
     * Удаление новости
     *
     * @OA\Post(
     *     operationId="/admin/web/news/remove",
     *     @OA\RequestBody(
     *          @DocModel(type=RemoveNewsRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Новость",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="bool", example=true)
     *     )
     * )
     * @OA\Tag(name="Admin\News")
     *
     * @param RemoveNewsRequest $request
     * @return JsonResponse
     */
    public function remove(RemoveNewsRequest $request): JsonResponse
    {
        $news = $this->getDoctrine()->getRepository(News::class)->find($request->id);
        if (!$news) {
            throw new NotFoundHttpException('Новость не найдена');
        }

        $this->getDoctrine()->getManager()->remove($news);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['status' => true]);
    }
}