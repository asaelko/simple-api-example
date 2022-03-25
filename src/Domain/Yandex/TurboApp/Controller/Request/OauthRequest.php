<?php

namespace App\Domain\Yandex\TurboApp\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class OauthRequest extends AbstractJsonRequest
{
    /**
     * OAuth-токен пользователя
     *
     * @Assert\Type(type="string", message="token должен быть строкой")
     * @Assert\NotBlank(message="token обязательное поле")
     */
    public string $token;
}