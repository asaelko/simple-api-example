<?php

namespace App\Domain\Core\Web\Controller\Client\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;

class ContactUsRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Имя и фамилия")
     */
    public string $name;

    /**
     * @OA\Property(description="Компания", nullable=true)
     */
    public ?string $company;

    /**
     * @OA\Property(description="Должность", nullable=true)
     */
    public ?string $position;

    /**
     * @OA\Property(description="Телефон")
     */
    public string $phone;

    /**
     * @OA\Property(description="Адрес электронной почты")
     */
    public string $email;
}
