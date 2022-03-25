<?php

namespace App\Domain\Core\Story\Controller;

use App\Domain\Core\Story\Controller\Request\BaseStoryRequest;
use App\Domain\Core\Story\Controller\Response\StoryResponse;
use App\Domain\Core\Story\Service\AdminStoryService;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Response\Common\BooleanResponse;
use CarlBundle\Response\Common\IterableListResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Контроллер для управления историями
 */
class AdminStoryController extends AbstractController
{
    private AdminStoryService $storyService;

    public function __construct(
        AdminStoryService $storyService
    )
    {
        $this->storyService = $storyService;
    }

    /**
     * Получить весь список историй
     *
     * Истории отсортированны по дате запуска
     *
     * @OA\Get(
     *     operationId="admin/stories/list"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив историй",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              type="integer",
     *              property="count",
     *              example=20
     *          ),
     *          @OA\Property(
     *              type="array",
     *              property="items",
     *              @OA\Items(
     *                  ref=@Model(type=StoryResponse::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Stories")
     *
     * @return IterableListResponse
     */
    public function getListAction(): IterableListResponse
    {
        return $this->storyService->getStories();
    }

    /**
     * Создать новую историю
     *
     * @OA\Post(
     *     operationId="admin/stories/create",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  ref=@Model(type=BaseStoryRequest::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт созданную историю",
     *     @Model(type=StoryResponse::class)
     * )
     *
     * @OA\Tag(name="Admin\Stories")
     *
     * @param BaseStoryRequest $storyRequest
     * @return StoryResponse
     * @throws InvalidValueException
     */
    public function createAction(BaseStoryRequest $storyRequest): StoryResponse
    {
        return $this->storyService->createFromRequest($storyRequest);
    }

    /**
     * Редактировать существующую историю
     *
     * @OA\Put(
     *     operationId="admin/stories/update",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      ref=@Model(type=BaseStoryRequest::class)
     *                  )
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт отредактиванную историю",
     *     @Model(type=StoryResponse::class)
     * )
     *
     * @OA\Response(
     *     response=404, description="История не найдена",
     *     @OA\JsonContent(
     *          @OA\Property(property="error", type="string", example="История не найдена")
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Stories")
     *
     * @param BaseStoryRequest $storyRequest
     * @param int $storyId
     * @return StoryResponse
     * @throws InvalidValueException
     */
    public function editAction(BaseStoryRequest $storyRequest, int $storyId): StoryResponse
    {
        return $this->storyService->updateFromRequest($storyId, $storyRequest);
    }

    /**
     * Перенести историю в архив
     *
     * @OA\Delete(
     *     operationId="admin/stories/archive",
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
     * @OA\Tag(name="Admin\Stories")
     *
     * @param int $storyId
     * @return BooleanResponse
     */
    public function archiveAction(int $storyId): BooleanResponse
    {
        $this->storyService->archiveStory($storyId);

        return new BooleanResponse(true);
    }
}
