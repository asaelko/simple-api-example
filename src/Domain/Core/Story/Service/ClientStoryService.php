<?php

namespace App\Domain\Core\Story\Service;

use App\Domain\Core\Story\Controller\Response\ClientStoryResponse;
use App\Domain\Core\Story\Controller\Response\StoryResponse;
use App\Domain\Core\Story\Storage\StoryViewsRedisStorage;
use AppBundle\Service\AppConfig;
use AppBundle\User\AbstractAuthorizableUser;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Story\Story;
use CarlBundle\ServiceRepository\Story\ClientStoryRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use function is_object;

/**
 * Сервис управления историями для клиентов
 */
class ClientStoryService
{
    private ClientStoryRepository $clientStoryRepository;
    private TokenStorageInterface $tokenStorage;
    private StoryViewsRedisStorage $redisStorage;
    private AppConfig $appConfig;

    public function __construct(
        StoryViewsRedisStorage $storyViewsStorage,
        TokenStorageInterface $tokenStorage,
        ClientStoryRepository $clientStoryRepository,
        AppConfig $appConfig
    )
    {
        $this->clientStoryRepository = $clientStoryRepository;
        $this->tokenStorage = $tokenStorage;
        $this->redisStorage = $storyViewsStorage;
        $this->appConfig = $appConfig;
    }

    /**
     * Получаем текущего пользователя в сервисе
     *
     * @return AbstractAuthorizableUser|null
     */
    private function getUser(): ?AbstractAuthorizableUser
    {
        $token = $this->tokenStorage->getToken();
        if (!$token || !is_object($token->getUser())) {
            return null;
        }
        $User = $token->getUser();
        assert($User instanceof AbstractAuthorizableUser);

        return $User;
    }

    /**
     * Получает доступные истории для пользователя
     *
     * @param int|null $brandId
     * @return array
     */
    public function getStories(?int $brandId = null): array
    {
        $brands = [];
        if (!$brandId) {
            $brands = $this->appConfig->getCurrentConfig()['brands'];
        } else {
            $brands[] = $brandId;
        }

        $stories = $this->clientStoryRepository->getStories($brands);

        if (!$this->getUser() || !$this->getUser()->isClient()) {
            return array_map(
                static fn(Story $story) => new StoryResponse($story),
                $stories
            );
        }

        $client = $this->getUser();
        assert($client instanceof Client);

        $viewedStories = $this->redisStorage->getAllClientViews($client);
        $stories = array_map(
            static fn(Story $story) => new ClientStoryResponse($story, isset($viewedStories[$story->getId()])),
            $stories
        );

        usort($stories, static function (ClientStoryResponse $StoryA, ClientStoryResponse $StoryB) {
            return $StoryA->viewed <=> $StoryB->viewed;
        });

        return $stories;
    }

    /**
     * Получает доступные истории для пользователя
     *
     * @param int|null $brandId
     * @return array
     */
    public function getWebStories(?int $brandId = null): array
    {
        return $this->clientStoryRepository->getWebStories($brandId);
    }

    /**
     * Получает историю по её ID
     *
     * @param int $storyId
     * @param int|null $brandId
     *
     * @return ClientStoryResponse
     */
    public function getStory(int $storyId, ?int $brandId = null): ClientStoryResponse
    {
        $story = $this->clientStoryRepository->find($storyId);

        if (!$story || ($story->getBrand() && $story->getBrand()->getId() !== $brandId)) {
            throw new NotFoundHttpException('История не найдена');
        }

        $client = $this->getUser();
        assert($client instanceof Client);

        return new ClientStoryResponse($story, $this->redisStorage->haveStoryBeenSeenByClient($story, $client));
    }

    /**
     * Фиксирует просмотр истории клиентом
     *
     * @param int $storyId
     */
    public function trackStoryView(int $storyId): void
    {
        $Story = $this->clientStoryRepository->find($storyId);
        if (!$Story) {
            throw new NotFoundHttpException('История не найдена');
        }

        $client = $this->getUser();
        if ($client && ($client instanceof Client)) {
            $this->redisStorage->trackStoryView($Story, $client);
        }
    }
}
