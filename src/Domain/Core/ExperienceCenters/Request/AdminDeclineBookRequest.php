<?php


namespace App\Domain\Core\ExperienceCenters\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class AdminDeclineBookRequest extends AbstractJsonRequest
{
    /**
     * @var int
     * @Assert\Type(type="integer", message="Id запроса должно быть числом")
     * @Assert\NotBlank(message="Не указан id запроса")
     */
    public int $requestId;
}