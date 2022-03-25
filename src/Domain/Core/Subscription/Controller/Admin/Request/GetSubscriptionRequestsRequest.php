<?php


namespace App\Domain\Core\Subscription\Controller\Admin\Request;


use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class GetSubscriptionRequestsRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Limit")
     * @Assert\Type(type="integer", message="limit должен быть целым числом")
     */
    public int $limit;

    /**
     * @OA\Property(description="Offset")
     * @Assert\Type(type="integer", message="offset должен быть целым числом")
     */
    public int $offset;

    /**
     * @OA\Property(description="С какого времени ищем заявки")
     */
    public ?int $fromTime = null;

    /**
     * @OA\Property(description="По какое время ищем заявки")
     */
    public ?int $toTime = null;
}
