<?php


namespace App\Domain\Core\Loan\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

class GetLoanProviderRequest extends AbstractJsonRequest
{
    /**
     * @Assert\Type(type="integer", message="id должно быть целым числом")
     * @OA\Property(description="id провайдера")
     */
    public int $id;
}