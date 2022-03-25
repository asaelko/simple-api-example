<?php

namespace App\Domain\Core\Client\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Запрос на указание/смену почтового адреса
 */
class SendEmailVerificationRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(type="string", example="v@carl-drive.ru", required={"email"})
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank(message="error.email_verification.incorrect_format")
     * @Assert\Email(message="error.email_verification.incorrect_format")
     */
    public string $email;
}
