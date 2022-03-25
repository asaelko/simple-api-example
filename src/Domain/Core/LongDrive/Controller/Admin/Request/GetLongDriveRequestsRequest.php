<?php


namespace App\Domain\Core\LongDrive\Controller\Admin\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class GetLongDriveRequestsRequest extends AbstractJsonRequest
{
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

    /**
     * @OA\Property(description="С какого времени ищем заявки")
     */
    public ?int $fromTime = null;

    /**
     * @OA\Property(description="По какое время ищем заявки")
     */
    public ?int $toTime = null;
}
