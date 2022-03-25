<?php


namespace App\Domain\Core\Loan\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

class LoanProviderListRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="количество записей")
     * @Assert\Type(type="integer", message="limit должно быть целым числом")
     * @Assert\NotBlank(message="limit обязяательное поле")
     */
    public int $limit;

    /**
     * @OA\Property(description="Сдвиг относительно 0")
     * @Assert\Type(type="integer", message="offset должно быть числом")
     * @Assert\NotBlank(message="offset обязятельное поле")
     */
    public int $offset;
}