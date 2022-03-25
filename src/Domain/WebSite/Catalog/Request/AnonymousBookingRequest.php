<?php

namespace App\Domain\WebSite\Catalog\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class AnonymousBookingRequest extends AbstractJsonRequest
{
    /**
     * Имя клиента
     *
     * @Assert\NotBlank(
     *     message="Пожалуйста, укажите ваше имя"
     * )
     */
    public string $name;

    /**
     * Фамилия клиента
     *
     * @Assert\Type(type="string")
     */
    public ?string $lastName = null;

    /**
     * Токен подтверждения номера телефона
     *
     * @Assert\NotBlank(message="Не подтвержден номер телефона")
     * @Assert\Uuid(message="Не подтвержден номер телефона")
     */
    public string $phoneToken;
}