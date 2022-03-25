<?php


namespace App\Domain\Core\Leasing\Request;


use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class LeasingRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Id провайдера для расчета лизинга")
     * @Assert\Type(type="integer", message="Поле providerId должно быть целым числом")
     * @Assert\NotBlank(message="Поле providerId обязательно")
     */
    public int $providerId;

    /**
     * @OA\Property(description="Id машины для расчета лизинга")
     * @Assert\Type(type="integer", message="Поле carId должно быть целым числом")
     * @Assert\NotBlank(message="Поле carId обязательно")
     */
    public int $carId;

    /**
     * @OA\Property(description="Процент первого плтежа")
     * @Assert\Type(type="integer", message="Поле firstPayPercent должно быть числом")
     * @Assert\NotBlank(message="Поле firstPayPercent обязательно")
     * @Assert\GreaterThan(value=0, message="Процент плтежа должен быть больше 0")
     */
    public int $firstPayPercent;

    /**
     * @OA\Property(description="Срок договора")
     * @Assert\Type(type="integer", message="Поле term должно быть целым числом")
     * @Assert\NotBlank(message="Поле term обязательно")
     */
    public int $term;
}