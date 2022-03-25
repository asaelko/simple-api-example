<?php

namespace App\Domain\Core\Story\Controller\Response;

use CarlBundle\Entity\Story\Story;
use CarlBundle\Entity\Story\StoryPart;
use CarlBundle\Response\Brand\BrandResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

/**
 * Объект ответа с историей
 */
class StoryResponse
{
    /**
     * Уникальный идентификатор истории
     *
     * @OA\Property(example=14)
     */
    public int $id;

    /**
     * Объект медиафайла для превью истории
     *
     * @OA\Property(type="object")
     */
    public MediaResponse $previewLink;

    /**
     * Объект медиафайла для превью истории
     *
     * @OA\Property(type="object")
     */
    public ?MediaResponse $previewHorizontalLink = null;

    /**
     * Заголовок истории
     *
     * @OA\Property(example="Новые модели Audi")
     */
    public string $header;

    /**
     * Коллекция частей истории
     *
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *          ref=@Model(type=ClientStoryPartResponse::class)
     *     )
     * )
     */
    public array $parts = [];

    /**
     * Дата создания истории
     *
     * @OA\Property(example=1612181693)
     */
    public int $createdAt;

    /**
     * Дата и время начала показа истории клиентам
     *
     * @OA\Property(example=1612190000)
     */
    public int $showStart;

    /**
     * Дата и время окончания показа истории клиентам
     *
     * @OA\Property(example=1613190000)
     */
    public int $showEnd;

    /**
     * Объект бренда, которому принадлежит история, если есть
     *
     * @OA\Property()
     */
    public ?BrandResponse $brand = null;

    /**
     * Параметр показа истории в вебе, в приложении или и там, и там
     *
     * @OA\Property(example=1)
     */
    public int $showIn;

    /**
     * Флаг просмотра истории пользователем
     *
     * @OA\Property(example=true)
     */
    public bool $viewed;

    public function __construct(Story $story)
    {
        $this->id = $story->getId();
        $this->header = $story->getHeader();
        $this->createdAt = $story->getCreatedAt()->getTimestamp();
        $this->showStart = $story->getShowStart()->getTimestamp();
        $this->showEnd = $story->getShowEnd()->getTimestamp();
        $this->showIn = $story->getShowIn();

        if ($story->getBrand()) {
            $this->brand = new BrandResponse($story->getBrand());
        }

        $this->parts = array_map(
            static fn(StoryPart $storyPart) => new ClientStoryPartResponse($storyPart),
            $story->getParts()->toArray()
        );

        $this->previewLink = new MediaResponse($story->getPreviewLink());
        if ($story->getPreviewHorizontalLink()) {
            $this->previewHorizontalLink = new MediaResponse($story->getPreviewHorizontalLink());
        }

        $this->viewed = false;
    }
}
