<?php

namespace App\Domain\Yandex\TurboApp\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class BookingRequest extends AbstractJsonRequest
{
    /**
     * Идентификатор выбранного автомобиля
     *
     * @Assert\NotBlank(message="Не выбран автомобиль")
     *
     * @Assert\Type("integer")
     */
    public int $car;

    /**
     * Выбранное время тест-драйва (unix timestamp)
     *
     * @Assert\NotBlank(message="Не выбрано время поездки")
     * @Assert\Type("integer")
     */
    public int $slot;

    /**
     * Выбранное место начала поездки
     *
     * @OA\Property(property="location", type="object", properties={
     *     @OA\Property(property="lat", type="number", format="double", example=55.752463),
     *     @OA\Property(property="lng", type="number", format="double", example=37.625972),
     *     @OA\Property(property="name", type="string", nullable=true, example="улица Малая Ордынка, 13с1")
     * }, required={"lat","lng"})
     *
     * @Assert\Type(type="array")
     * @Assert\Collection(
     *     fields = {
     *          "lat" = @Assert\Required({
     *              @Assert\Type(type="float", message="Некорректный формат координат точки начала поездки"),
     *              @Assert\NotBlank(message="Не выбрана точка начала поездки")
     *          }),
     *          "lng" = @Assert\Required({
     *              @Assert\Type(type="float", message="Некорректный формат координат точки начала поездки"),
     *              @Assert\NotBlank(message="Не выбрана точка начала поездки")
     *          }),
     *          "name" = @Assert\Optional({
     *              @Assert\Type(type="string")
     *          })
     *     }
     * )
     */
    public array $location = [];
}
