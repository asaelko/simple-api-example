<?php

namespace App\Domain\Core\Story\Storage;

use CarlBundle\Entity\Client;
use CarlBundle\Entity\Story\Story;
use DateTime;
use Predis\Client as PredisClient;

/**
 * Redis-storage для хранения данных по просмотрам историй клиентами
 */
class StoryViewsRedisStorage
{
    private const STORY_VIEW_KEY = 'story_view:story:%d';
    private const CLIENT_VIEW_KEY = 'story_view:client:%d';

    private PredisClient $redisStorage;

    public function __construct(
        PredisClient $redisStorage
    )
    {
        $this->redisStorage = $redisStorage;
    }

    /**
     * Ключ для хранения просмотров конкретной истории
     *
     * @param Story $Story
     * @return string
     */
    private function getStoryStorageKey(Story $Story): string
    {
        return sprintf(self::STORY_VIEW_KEY, $Story->getId());
    }

    /**
     * Ключ для хранения просмотров историй клиентом
     *
     * @param Client $Client
     * @return string
     */
    private function getClientStorageKey(Client $Client): string
    {
        return sprintf(self::CLIENT_VIEW_KEY, $Client->getId());
    }

    /**
     * Получает список ID клиентов, смотревших машину
     *
     * @param Story $Story
     *
     * @return array
     */
    public function getAllStoryViewers(Story $Story): array
    {
        return $this->redisStorage->zrange($this->getStoryStorageKey($Story), 0, -1, ['WITHSCORES' => true]);
    }

    /**
     * Получает ID пользователей, посмотревших машину в определенный отрезок времени
     *
     * @param Story $Story
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @return array
     */
    public function getStoryViewerPerTimePeriod(Story $Story, DateTime $startTime, DateTime $endTime): array
    {
        return $this->redisStorage->zrangebyscore(
            $this->getStoryStorageKey($Story),
            $startTime->getTimestamp(),
            $endTime->getTimestamp(),
            ['WITHSCORES']
        );
    }

    /**
     * Получает список всех просмотров клиента
     *
     * @param Client $Client
     *
     * @return array
     */
    public function getAllClientViews(Client $Client): array
    {
        return $this->redisStorage->zrange($this->getClientStorageKey($Client),0, -1, ['WITHSCORES' => true]);
    }

    /**
     * Получает список просмотров клиентом машин за определенный отрезок времени
     *
     * @param Client $Client
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @return array
     */
    public function getClientViewsPerTimePeriod(Client $Client, DateTime $startTime, DateTime $endTime): array
    {
        return $this->redisStorage->zrangebyscore(
            $this->getClientStorageKey($Client),
            $startTime->getTimestamp(),
            $endTime->getTimestamp(),
            ['WITHSCORES']
        );
    }

    /**
     * Проверяет, смотрел ли клиент указанную историю
     *
     * @param Story $Story
     * @param Client $Client
     * @return bool
     */
    public function haveStoryBeenSeenByClient(Story $Story, Client $Client): bool
    {
        return (bool) $this->redisStorage->zscore($this->getStoryStorageKey($Story), $Client->getId());
    }

    /**
     * Фиксирует просмотр истории клиентом
     *
     * @param Story $story
     * @param Client $client
     */
    public function trackStoryView(Story $story, Client $client): void
    {
        $this->redisStorage->zadd($this->getStoryStorageKey($story), [$client->getId() => time()]);
        $this->redisStorage->zadd($this->getClientStorageKey($client), [$story->getId() => time()]);
    }
}
