<?php

namespace App\Domain\Core\Geo\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class GeoPointRequest extends AbstractJsonRequest
{
    /**
     * @var float|null
     *
     * @Assert\Type(type="float", message="lat должно быть дробным числом")
     */
    public ?float $lat = null;

    /**
     * @var float|null
     *
     * @Assert\Type(type="float", message="lon должно быть дробным числом")
     */
    public ?float $lon = null;
}
