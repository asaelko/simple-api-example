<?php


namespace App\Domain\WebSite\News\Response;
use App\Domain\Core\Story\Controller\Response\MediaResponse;
use App\Entity\News;
use OpenApi\Annotations as OA;

class NewsResponse
{
    /**
     * @OA\Property(description="Id")
     */
    public int $id;

    /**
     * @OA\Property(description="Заголовок")
     */
    public string $title;

    /**
     * @OA\Property(description="Короткое описание")
     */
    public string $shortDescription;

    /**
     * @OA\Property(description="Текст новости")
     */
    public ?string $description;

    /**
     * @OA\Property(description="Признак активности")
     */
    public bool $isActive;

    /**
     * @OA\Property(description="фотография")
     * @var MediaResponse
     */
    public MediaResponse $photo;

    /**
     * @OA\Property(description="timstamp")
     */
    public int $dateCreate;

    /**
     * @OA\Property(description="Ссылка если есть")
     */
    public ?string $link;

    /**
     * @OA\Property(description="Текст действия, если есть")
     */
    public ?string $actionText;

    /**
     * @OA\Property(description="Идентификатор бренда, к которому привязана новость", nullable=true)
     */
    public ?int $brandId;

    /**
     * @OA\Property(description="Идентификатор модели, к которой привязана новость", nullable=true)
     */
    public ?int $modelId;

    public function __construct(News $news)
    {
        $this->id = $news->getId();
        $this->title = $news->getTitle();
        $this->shortDescription = $news->getShortDescription();
        $this->description = $news->getDescription();
        $this->isActive = $news->getIsActive();
        $this->photo = new MediaResponse($news->getPhoto());
        $this->dateCreate = $news->getDateCreate()->getTimestamp();
        $this->link = $news->getLink();
        $this->actionText = $news->getActionText();

        if ($news->getBrand()) {
            $this->brandId = $news->getBrand()->getId();
        }

        if ($news->getModel()) {
            $this->modelId = $news->getModel()->getId();
        }
    }
}