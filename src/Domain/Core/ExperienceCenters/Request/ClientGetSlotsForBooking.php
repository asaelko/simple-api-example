<?php


namespace App\Domain\Core\ExperienceCenters\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class ClientGetSlotsForBooking extends AbstractJsonRequest
{
    /**
     * @var int
     *
     * @Assert\Type(type="integer", message="Id должен быть целым числом")
     * @Assert\NotBlank(message="Не передан center id")
     */
    public int $centerId;
}