<?php


namespace App\Domain\Infrastructure\CarPrice\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

class CreateCarPriceRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="timestamp времени когда клиент хочет назначить встречу")
     * @Assert\Type(type="integer", message="time должно быть целым числом")
     * @Assert\NotBlank(message="Поле time обязательно для заполнения")
     */
    public int $time;

    /**
     * @OA\Property(description="Id точки продаж")
     * @Assert\Type(type="integer", message="Поле locationId должно числом")
     * @Assert\NotBlank(message="Поле locationId обязательно для заполнения")
     */
    public int $locationId;

    /**
     * @OA\Property(description="Id тачки клиента на продажу")
     * @Assert\Type(type="integer", message="Поле clientCar должно быть целым числом")
     * @Assert\NotBlank(message="Поле clientCar обязательно для заполнения")
     */
    public int $clientCar;
}