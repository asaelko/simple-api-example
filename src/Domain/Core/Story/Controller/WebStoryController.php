<?php

namespace App\Domain\Core\Story\Controller;

use App\Domain\Core\Story\Controller\Response\StoryResponse;
use App\Domain\Core\Story\Service\WebStoryService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Контроллер отображения сторис для веб-клиентов
 */
class WebStoryController extends AbstractController
{
    private WebStoryService $webStoryService;

    public function __construct(
        WebStoryService $webStoryService
    )
    {
        $this->webStoryService = $webStoryService;
    }

    /**
     * Получить сторисы для показа в вебе
     *
     * @OA\Get(
     *     operationId="web/stories/list",
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив историй для показа",
     *     @OA\JsonContent(
     *     @OA\Property(
     *          type="array",
     *          @OA\Items(
     *              ref=@Model(type=StoryResponse::class)
     *          )
     *         )
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Web\Stories")
     *
     * @return array|StoryResponse[]
     */
    public function getStoriesAction(): array
    {
        return $this->webStoryService->getWebStories();
    }

    /**
     * Получить сторисы для показа клиенту для конкретного бренда
     *
     * @OA\Get(
     *     operationId="web/brand/stories/list",
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив историй для показа",
     *     @OA\JsonContent(
     *     @OA\Property(
     *      type="array",
     *          @OA\Items(
     *              ref=@Model(type=StoryResponse::class)
     *          )
     *      )
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Web\Stories")
     *
     * @param int|null $brandId
     * @return array|StoryResponse[]
     */
    public function getStoriesByBrandAction(int $brandId): array
    {
        return $this->webStoryService->getWebStories($brandId);
    }
}
