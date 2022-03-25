<?php

namespace App\Domain\WebSite\Catalog\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class ListSubscriptionsRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Количество записей на странице")
     * @Assert\Type(type="integer", message="limit должен быть целым числом")
     */
    public int $limit = 10;

    /**
     * @OA\Property(description="Сдвиг относительно 0 записи")
     * @Assert\Type(type="integer", message="offset должен быть целым числом")
     */
    public int $offset = 0;

    /**
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $brands = [];

    /**
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $models = [];

    /**
     * @OA\Property(type="string", description="Текстовый поиск по бренду и модели")
     */
    public ?string $search = null;
}
