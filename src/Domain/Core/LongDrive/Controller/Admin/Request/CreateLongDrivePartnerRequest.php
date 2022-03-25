<?php

namespace App\Domain\Core\LongDrive\Controller\Admin\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class CreateLongDrivePartnerRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Название партнера")
     * @Assert\Type(type="string", message="partner name должно быть строкой")
     */
    public string $partnerName;

    /**
     * @OA\Property(description="Описание партнера")
     * @Assert\Type(type="string", message="description должно быть строкой")
     */
    public string $description;

    /**
     * @OA\Property(description="Короткое описание партнера")
     * @Assert\Type(type="string", message="shortDescription должно быть строкой")
     */
    public string $shortDescription;

    /**
     * @OA\Property(description="Полное название организации партнера")
     * @Assert\Type(type="string", message="fullOrganizationName name должно быть строкой")
     */
    public string $fullOrganizationName;

    /**
     * @OA\Property(description="Почта партнера")
     * @Assert\Email(message="Не вервный формат почты")
     */
    public string $email;

    /**
     * @OA\Property(description="Идентификатор логотипа партнера")
     */
    public int $logoId;
}