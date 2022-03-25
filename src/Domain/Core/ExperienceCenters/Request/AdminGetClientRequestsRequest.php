<?php


namespace App\Domain\Core\ExperienceCenters\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class AdminGetClientRequestsRequest extends AbstractJsonRequest
{
    /**
     * @var int
     * @Assert\Type(type="integer", message="Id центра должно быть целым числом")
     * @Assert\NotBlank(message="Не передан id центра")
     */
    public int $centerId;
}