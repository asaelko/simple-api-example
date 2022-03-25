<?php

namespace App\Domain\Core\Model\Controller\Response;

use CarlBundle\Entity\Section;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;

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