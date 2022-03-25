<?php

namespace App\Domain\Core\Brand\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;

/**
 * Запрос на фильтрацию бренда
 */
class BrandFilterRequest extends AbstractJsonRequest
{
    /**
     * Если true, вернет только те бренды, у которых были поездки
     *
     * @OA\Property()
     */
    public bool $hasActivity = false;

    /**
     * Набор фильтров бренда
     *
     * @OA\Property(
     *     type="object"
     * )
     */
    public array $filters = [];
}
