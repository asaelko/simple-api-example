<?php

namespace App\Domain\Core\Story\Controller;

use App\Domain\Core\Story\Controller\Response\ClientStoryResponse;
use App\Domain\Core\Story\Service\ClientStoryService;
use CarlBundle\Response\Common\BooleanResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Контроллер работы со сторис для мобильных клиентов
 */
class ClientStoryController extends AbstractController
{
    private ClientStoryService $clientStoryService;

    public function __construct(
        ClientStoryService $clientStoryService
    )
    {
        $this->clientStoryService = $clientStoryService;
    }

    /**
     * Получить истории для показа клиенту
     *
     * Истории отсортированы в порядке признака просмотренности – сначала идут непросмотренные истории, потом просмотренные
     *
     * @OA\Get(
     *     operationId="client/stories/list",
     *     @OA\Parameter(
     *          name="brandId",
     *          in="query",
     *          description="Получить сторис только для конкретного бренда",
     *          @OA\Schema(type="integer")
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив историй для показа",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              type="array",
     *              @OA\Items(
     *                  ref=@Model(type=ClientStoryResponse::class)
     *              )
     *           )
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Client\Stories")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStoriesAction(Request $request): JsonResponse
    {
        $brandId = $request->query->get('brandId');
        return new JsonResponse($this->clientStoryService->getStories($brandId));
    }

    /**
     * Получить историю по её ID
     *
     * @OA\Get(
     *     operationId="client/stories/show"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт историю",
     *     @Model(type=ClientStoryResponse::class)
     * )
     *
     * @OA\Response(
     *     response=404, description="История не найдена",
     *     @OA\JsonContent(
     *          @OA\Property(property="error", type="string", example="История не найдена")
     *     )
     * )
     *
     * @OA\Tag(name="Client\Stories")
     *
     * @param int $storyId
     * @return JsonResponse
     */
    public function getStoryAction(int $storyId): JsonResponse
    {
        return new JsonResponse($this->clientStoryService->getStory($storyId));
    }

    /**
     * Получить историю для бренда по её ID
     *
     * @OA\Get(
     *     operationId="client/brand/stories/show"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт историю",
     *     @Model(type=ClientStoryResponse::class)
     * )
     *
     * @OA\Response(
     *     response=404, description="История не найдена",
     *     @OA\JsonContent(
     *          @OA\Property(property="error", type="string", example="История не найдена")
     *     )
     * )
     *
     * @OA\Tag(name="Client\Stories")
     *
     * @param int $storyId
     * @param int $brandId
     * @return JsonResponse
     */
    public function getBrandStoryAction(int $storyId, int $brandId): JsonResponse
    {
        return new JsonResponse($this->clientStoryService->getStory($storyId, $brandId));
    }

    /**
     * Пометить сторис просмотренной
     *
     * @OA\Put(
     *     operationId="client/stories/viewed"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт результат выполнения запроса",
     *     @Model(type=BooleanResponse::class)
     * )
     *
     * @OA\Response(
     *     response=404, description="История не найдена",
     *     @OA\JsonContent(
     *          @OA\Property(property="error", type="string", example="История не найдена")
     *     )
     * )
     *
     * @OA\Tag(name="Client\Stories")
     *
     * @param int $storyId
     *
     * @return JsonResponse
     */
    public function markStoryAsViewedAction(int $storyId): JsonResponse
    {
        $this->clientStoryService->trackStoryView($storyId);

        return new JsonResponse(new BooleanResponse(true));
    }
}
