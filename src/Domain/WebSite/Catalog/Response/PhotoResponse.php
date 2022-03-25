<?php

namespace App\Domain\WebSite\Catalog\Response;

use CarlBundle\Entity\Photo;
use OpenApi\Annotations as OA;

/**
 * Объект фотографии
 */
class PhotoResponse
{
    /**
     * Уникальный идентификатор медиафайла
     *
     * @OA\Property(example=1)
     */
    public int $id;

    /**
     * Высота медиаконтента
     *
     * @OA\Property(example=1080)
     */
    public ?int $height;

    /**
     * Ширина медиаконтента
     *
     * @OA\Property(example=1920)
     */
    public ?int $width;

    /**
     * Текстовое описание типа фотографии
     *
     * @OA\Property(example="profile")
     */
    public ?string $type;

    /**
     * Ссылка на медиафайл
     *
     * @OA\Property(example="https://cdn.carl-drive.ru/uploads/7732ae2c2bec445795126ad1906ffccf/original.jpeg")
     */
    public string $absolutePath;

    public function __construct(Photo $photo)
    {
        $this->id = $photo->getId();
        $this->height = $photo->getHeight();
        $this->width = $photo->getWidth();
        $this->type = $photo->getType();
        $this->absolutePath = $photo->getAbsolutePath();
    }
}
