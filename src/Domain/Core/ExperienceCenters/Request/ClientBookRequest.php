<?php


namespace App\Domain\Core\ExperienceCenters\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class ClientBookRequest extends AbstractJsonRequest
{
    /**
     * @var int
     * @Assert\Type(type="integer", message="Id для бронирования должно быть целым числом")
     * @Assert\NotBlank(message="Не передан id для бронирования")
     */
    public int $slotId;

    /**
     * @var string|null
     */
    public ?string $paymentId;
}