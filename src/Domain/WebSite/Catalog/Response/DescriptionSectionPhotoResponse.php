<?php

namespace App\Domain\WebSite\Catalog\Response;

use CarlBundle\Entity\Photo;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;

class DescriptionSectionPhotoResponse
{
    /**
     * Фотография секции описания
     *
     * @OA\Property(ref=@DocModel(type=PhotoResponse::class)))
     */
    public PhotoResponse $photo;

    /**
     * Текст к фотографии секции описания
     *
     * @OA\Property(nullable=true, example="Bentayga Diesel — первый дизельный в истории марки — самый быстрый и роскошный дизельный автомобиль в мире. Новый 4,0-литровый дизельный двигатель с восемью цилиндрами и 32 клапанами разгоняет автомобиль с места до первой сотни всего за 4,8 с и позволяет достичь максимальной отметки на спидометре 270 км/ч")
     */
    public ?string $description;

    public function __construct(Photo $sectionPhoto)
    {
        $this->photo = new PhotoResponse($sectionPhoto);
        $this->description = $sectionPhoto->getDescription();
    }
}
