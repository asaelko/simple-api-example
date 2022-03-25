<?php

namespace App\Domain\Core\Story\Service;

use App\Domain\Core\Story\Controller\Request\BaseStoryRequest;
use App\Domain\Core\Story\Controller\Response\StoryResponse;
use CarlBundle\Entity\Story\Story;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Factory\Story\StoryRequestFactory;
use CarlBundle\Response\Common\IterableListResponse;
use CarlBundle\ServiceRepository\Admin\Story\AdminStoryRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Сервис управления историями
 */
class AdminStoryService
{
    private AdminStoryRepository $storyRepository;
    private StoryRequestFactory $requestFactory;

    public function __construct(
        AdminStoryRepository $storyRepository,
        StoryRequestFactory $requestFactory
    )
    {
        $this->storyRepository = $storyRepository;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Получаем все доступные истории, отсортированные по дате запуска
     * @param int|null $limit
     * @param int|null $offset
     * @return IterableListResponse
     */
    public function getStories(?int $limit = null, ?int $offset = null): IterableListResponse
    {
        if ($limit !== null && $offset !== null) {
            $stories = $this->storyRepository->getStories($limit, $offset);
            $count = count($this->storyRepository->findAll());
        } else {
            $stories = $this->storyRepository->findAll();
            $count = count($stories);
        }


        $stories = array_map(
            static fn(Story $story) => new StoryResponse($story),
            $stories
        );

        return new IterableListResponse($stories, $count, $offset ?? 0);
    }

    /**
     * Создаем новую историю по запросу
     *
     * @param BaseStoryRequest $storyRequest
     * @return StoryResponse
     * @throws InvalidValueException
     */
    public function createFromRequest(BaseStoryRequest $storyRequest): StoryResponse
    {
        $Story = $this->requestFactory->create($storyRequest);

        $this->storyRepository->persist($Story);
        $this->storyRepository->flush();

        return new StoryResponse($Story);
    }

    /**
     * Обновляем существующую историю по HTTP-запросу
     *
     * @param int $storyId
     * @param BaseStoryRequest $storyRequest
     * @return StoryResponse
     * @throws InvalidValueException
     */
    public function updateFromRequest(int $storyId, BaseStoryRequest $storyRequest): StoryResponse
    {
        $Story = $this->storyRepository->find($storyId);

        if (!$Story) {
            throw new NotFoundHttpException('История не найдена');
        }

        $this->requestFactory->update($Story, $storyRequest);
        $this->storyRepository->flush();

        return new StoryResponse($Story);
    }

    /**
     * Архивируем историю (удаляем)
     *
     * @param int $storyId
     * @return void
     */
    public function archiveStory(int $storyId): void
    {
        $Story = $this->storyRepository->find($storyId);

        if (!$Story) {
            throw new NotFoundHttpException('История не найдена');
        }

        $this->storyRepository->remove($Story);
        $this->storyRepository->flush();
    }
}
