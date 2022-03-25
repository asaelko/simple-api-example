<?php


namespace App\Domain\Core\Loan\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

class UpdateLoanRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Полное название организации если есть")
     * @Assert\Type(type="string", message="Название организации должно быть строкой")
     */
    public ?string $organizationName = null;
}