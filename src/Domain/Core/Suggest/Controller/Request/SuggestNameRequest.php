<?php

namespace App\Domain\Core\Suggest\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class SuggestNameRequest extends AbstractJsonRequest
{
    /**
     * @var string
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank(message="Поле name обязательно")
     * @Assert\Length(min="1", minMessage="Минимум 1 символ")
     */
    public string $name;

    /**
     * @var int
     *
     * @Assert\Type(type="integer")
     * @Assert\GreaterThan(value="0", message="Поле count должно быть больше 0")
     * @Assert\NotBlank(message="Поле name обязательно")
     */
    public int $count;
}