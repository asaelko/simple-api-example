<?php

namespace App\Domain\Core\Model\Controller\Response;

use CarlBundle\Entity\Brand;
use OpenApi\Annotations as OA;

/**
 * Короткий респонз бренда для модели
 */
class BrandResponse
{
    /**
     * Уникальный идентификатор бренда
     *
     * @OA\Property(example=51)
     */
    public int $id;

    /**
     * Название бренда
     *
     * @OA\Property(example="BMW")
     */
    public string $name;

    public function __construct(Brand $brand)
    {
        $this->id = $brand->getId();
        $this->name = $brand->getName();
    }
}
