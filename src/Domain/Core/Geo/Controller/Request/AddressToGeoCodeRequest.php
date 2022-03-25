<?php

namespace App\Domain\Core\Geo\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class AddressToGeoCodeRequest extends AbstractJsonRequest
{
    /**
     * @var string
     *
     * @Assert\Type(type="string", message="address должно быть строкой")
     * @Assert\NotBlank(message="address обязательный параметер")
     */
    public string $address;
}
