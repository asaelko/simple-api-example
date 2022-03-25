<?php

namespace App\Domain\Core\Partners\Controller\Response;

use CarlBundle\Entity\Partner;
use OpenApi\Annotations as OA;

/**
 * Ответ на запрос партнеров
 */
class PartnerResponse
{
    /** @OA\Property(example=1) */
    public int $id;

    /** @OA\Property(example="Лукойл") */
    public string $name;

    /** @OA\Property(example="Топливная карта в подарок") */
    public string $subtitle;

    /** @OA\Property(example="Подробное описание акции для отображения на экране акции") */
    public string $description;

    /** @OA\Property(example="https://cdn.carl-drive.ru/photos/blank.png") */
    public string $logo;

    /** @OA\Property(example="https://cdn.carl-drive.ru/photos/blank.png") */
    public ?string $splashPhoto;

    public function __construct(Partner $partner)
    {
        $this->id = $partner->getId();
        $this->name = $partner->getName();
        $this->subtitle = $partner->getSubtitle();
        $this->description = $partner->getDescription();
        $this->logo = $partner->getPhoto()->getAbsolutePath();
        if ($partner->getSplashPhoto()) {
            $this->splashPhoto = $partner->getSplashPhoto()->getAbsolutePath();
        }
    }
}