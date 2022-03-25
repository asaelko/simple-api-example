<?php


namespace App\Domain\Core\LongDrive\Controller\Admin\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class ListLongDriveStockRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Фильтр по партнеру", type="array", @OA\Items(type="integer"))
     */
    public array $partnersId = [];

    /**
     * @OA\Property(description="Фильтр по бренду", type="array", @OA\Items(type="integer"))
     */
    public array $brandsId = [];

    /**
     * @OA\Property(description="Фильтр по модели", type="array", @OA\Items(type="integer"))
     */
    public array $modelsId = [];

    /**
     * @OA\Property(description="Limit")
     * @Assert\Type(type="integer", message="limit должен быть целым числом")
     */
    public int $limit = 20;

    /**
     * @OA\Property(description="Offset")
     * @Assert\Type(type="integer", message="offset должен быть целым числом")
     */
    public int $offset = 0;
}
