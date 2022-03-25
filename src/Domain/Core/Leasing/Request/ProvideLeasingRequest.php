<?php


namespace App\Domain\Core\Leasing\Request;


use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class ProvideLeasingRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Id машины дл якоторой будет создан запрос")
     * @Assert\NotBlank(message="Поле carId обязательно")
     * @Assert\Type(type="integer", message="carId должно быть целым числом")
     */
    public int $carId;

    /**
     * @OA\Property(description="Ежемесячный платеж по лизингу")
     * @Assert\Type(type="float", message="Ежемесячный плавтеж должен быть числом")
     * @Assert\NotBlank(message="Ежемесячный платеж обязательное поле")
     * @Assert\GreaterThan(value=0, message="Ежемесячный платеж должен быть больше 0")
     */
    public float $monthPay;

    /**
     * @OA\Property(description="Id провайдера лизинга")
     * @Assert\NotBlank(message="LeasingProviderId обязательное поле")
     * @Assert\Type(type="integer", message="LeasingProviderId должно быть целым числом")
     */
    public int $leasingProviderId;

    /**
     * @OA\Property(description="Срок лизинга")
     * @Assert\NotBlank(message="term обязательное поле")
     * @Assert\Type(type="integer", message="term должно быть целым числом")
     */
    public int $term;

    /**
     * @OA\Property(description="Процент первого платежа")
     * @Assert\NotBlank(message="firstPayPercent обязательное поле")
     * @Assert\Type(type="integer", message="firstPayPercent должно быть целым числом")
     */
    public int $firstPayPercent;
}