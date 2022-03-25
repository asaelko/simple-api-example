<?php

namespace App\Domain\Core\Brand\Controller\Response;

use App\Domain\Core\Brand\Controller\Response\Client\PhotoResponse;
use CarlBundle\Entity\Brand;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;

/**
 * Базовый объект бренда для ответа API
 */
class BaseBrandResponse
{
    /**
     * Уникальный идентификатор бренда
     *
     * @OA\Property(example=31)
     */
    public int $id;

    /**
     * Описание бренда если есть
     * @OA\Property(example="Описание бренда")
     */
    public ?string $description;

    /**
     * Логотип бренда
     *
     * @OA\Property(ref=@DocModel(type=PhotoResponse::class))
     */
    public PhotoResponse $logo;

    /**
     * Наименование бренда
     *
     * @OA\Property(example="Audi")
     */
    public string $name;

    public function __construct(Brand $brand)
    {
        $this->id = $brand->getId();
        $this->name = trim($brand->getName());
        $this->description = $brand->getDescription();

        $logo = $brand->getLogo();
        if ($logo) {
            $this->logo = new PhotoResponse($logo);
        }
    }
}
