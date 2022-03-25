<?php

namespace App\Domain\WebSite\Catalog\Response;

use CarlBundle\Entity\Section;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;

class DescriptionSectionResponse
{
    /**
     * Название секции описания
     *
     * @OA\Property(example="Динамика")
     */
    public string $name;

    /**
     * Фотографии секции описания
     *
     * @OA\Property(type="array", @OA\Items(ref=@DocModel(type=DescriptionSectionPhotoResponse::class)))
     */
    public array $photos;

    public function __construct(Section $section)
    {
        $this->name = $section->getName();

        foreach ($section->getPhotos() as $photo) {
            $this->photos []= new DescriptionSectionPhotoResponse($photo);
        }
    }
}