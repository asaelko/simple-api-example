<?php

namespace App\Domain\Core\Story\Service;

use App\Domain\Core\Story\Controller\Response\StoryResponse;
use CarlBundle\Entity\Story\Story;
use CarlBundle\ServiceRepository\Story\ClientStoryRepository;

/**
 * Сервис для работы со стори из веба
 */
class WebStoryService
{
    private ClientStoryRepository $clientStoryRepository;

    public function __construct(
        ClientStoryRepository $clientStoryRepository
    )
    {
        $this->clientStoryRepository = $clientStoryRepository;
    }

    /**
     * Получает доступные истории для пользователя
     *
     * @param int|null $brandId
     * @return array
     */
    public function getWebStories(?int $brandId = null): array
    {
        $stories = $this->clientStoryRepository->getWebStories($brandId);

        return array_map(
            static fn(Story $story) => new StoryResponse($story),
            $stories
        );
    }
}
