<?php


namespace App\Domain\WebSite\News\Request;


use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class CreateNewsRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Заголовок", required={"title"})
     * @Assert\Type(type="string", message="заголовок должен быть строкой")
     */
    public string $title;

    /**
     * @OA\Property(description="Короткое описание", required={"shortDescription"})
     * @Assert\Type(type="string", message="Короткое описание должно быть строкой")
     */
    public string $shortDescription;

    /**
     * @OA\Property(description="Текст новости")
     * @Assert\Type(type="string", message="Полное описание должно быть строкой")
     */
    public ?string $description;

    /**
     * @OA\Property(description="Признак активности")
     * @Assert\Type(type="boolean", message="Признак активности должен быть лигическим")
     */
    public bool $isActive = false;

    /**
     * @OA\Property(description="Айди фотографии", required={"photo"})
     * @Assert\Type(type="int", message="Id фотографии должно быть целым числом")
     */
    public int $photo;

    /**
     * @OA\Property(description="Ссылка, если есть")
     */
    public ?string $link = null;

    /**
     * @OA\Property(description="Текст кнопки, если есть")
     */
    public ?string $actionText = null;

    /**
     * @OA\Property(description="Бренд, к которому относится новость")
     */
    public ?int $brandId = null;

    /**
     * @OA\Property(description="Модель, к которой относится новость")
     */
    public ?int $modelId = null;
}