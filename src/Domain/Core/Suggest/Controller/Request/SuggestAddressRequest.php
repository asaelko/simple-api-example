<?php

namespace App\Domain\Core\Suggest\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class SuggestAddressRequest extends AbstractJsonRequest
{
    /**
     * @var string
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank(message="Поле address обязательно")
     */
    public string $address;

    /**
     * @var int|null
     *
     * @Assert\Type(type="integer")
     */
    public ?int $city = null;

    /**
     * @var int
     *
     * @Assert\Type(type="integer")
     * @Assert\GreaterThan(value="0", message="Поле count должно быть больше 0")
     */
    public int $count = 5;
}