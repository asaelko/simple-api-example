<?php

namespace App\Domain\Core\Story\Controller\Response;

use CarlBundle\Entity\Story\StoryPart;
use OpenApi\Annotations as OA;

/**
 * Объект ответа по части истории для клиента
 */
class ClientStoryPartResponse
{
    /**
     * Уникальный идентификатор части истории
     *
     * @OA\Property(example=1)
     */
    public int $id;

    /**
     * Медиа-файл данной части истории
     */
    public MediaResponse $media;

    /**
     * Медиа-файл данной части истории в горизонтальном формате
     */
    public ?MediaResponse $mediaHorizontal = null;

    /**
     * Время показа части в секундах
     *
     * @OA\Property(example=15)
     */
    public int $showTime;

    /**
     * Текст на кнопке Call to action, если она есть
     *
     * @OA\Property(example="Забронировать!")
     */
    public ?string $actionText = null;

    /**
     * Ссылка для перехода по нажатию на кнопку Call to action, если она есть
     *
     * @OA\Property(example="carl://car/123")
     */
    public ?string $actionLink = null;

    /**
     * Время создания части истории
     *
     * @OA\Property(example=1612181693)
     */
    public int $createdAt;

    public function __construct(StoryPart $storyPart)
    {
        $this->id = $storyPart->getId();
        $this->showTime = $storyPart->getShowTime();
        $this->createdAt = $storyPart->getCreatedAt()->getTimestamp();

        $this->actionText = $storyPart->getActionText();
        $this->actionLink = $storyPart->getActionLink();

        $this->media = new MediaResponse($storyPart->getMedia());
        if ($storyPart->getMediaHorizontal()) {
            $this->mediaHorizontal = new MediaResponse($storyPart->getMediaHorizontal());
        }
    }
}
