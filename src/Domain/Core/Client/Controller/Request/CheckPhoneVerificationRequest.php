<?php

namespace App\Domain\Core\Client\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Запрос проверки кода верификации номера телефона клиента
 */
class CheckPhoneVerificationRequest extends AbstractJsonRequest
{
    /**
     * @Assert\Type(type="string", message="Идентификатор сессии подтверждения имеет неверный тип")
     * @Assert\NotBlank(message="Идентификатор сессии подтверждения обязателен")
     */
    public string $verificationId;

    /**
     * @Assert\Type(type="string", message="Код подтверждения номера телефона имеет не верный тип")
     * @Assert\NotBlank(message="Код подтверждения номера телефона обязателен")
     */
    public string $code;
}
