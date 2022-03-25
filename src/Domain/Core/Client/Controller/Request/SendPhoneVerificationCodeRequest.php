<?php

namespace App\Domain\Core\Client\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Запрос на подтверждение номера телефона
 */
class SendPhoneVerificationCodeRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(type="string", example="79001234567", required={"phone"})
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank(message="error.phone_verification.incorrect_format")
     * @Assert\Regex(
     *     pattern="/^\d{11}$/",
     *     match=true,
     *     message="error.phone_verification.incorrect_format"
     * )
     */
    public string $phone;
}
