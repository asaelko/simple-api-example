<?php

namespace App\Domain\Core\Story\Controller\Response;

use CarlBundle\Entity\Media\Media;
use OpenApi\Annotations as OA;

class MediaResponse
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
     * Тип медиаконтента (video || photo)
     *
     * @OA\Property(example="photo")
     */
    public string $type;

    /**
     * Превью, если есть
     *
     * @OA\Property()
     */
    public ?MediaResponse $preview;

    /**
     * Ссылка на медиафайл
     *
     * @OA\Property(example="https://cdn.carl-drive.ru/uploads/7732ae2c2bec445795126ad1906ffccf/original.jpeg")
     */
    public string $absolutePath;

    /**
     * Дата создания медиа-превью
     *
     * @OA\Property(example=1612190000)
     */
    public int $createdAt;

    public function __construct(Media $media)
    {
        $this->id = $media->getId();
        $this->height = $media->getHeight();
        $this->width = $media->getWidth();
        $this->type = $media->getType();
        $this->absolutePath = $media->getAbsolutePath();
        $this->createdAt = $media->getCreatedAt();

        if ($media->getPreview()) {
            $this->preview = new MediaResponse($media->getPreview());
        }
    }
}
