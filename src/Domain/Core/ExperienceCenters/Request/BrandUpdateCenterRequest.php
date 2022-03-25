<?php


namespace App\Domain\Core\ExperienceCenters\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class BrandUpdateCenterRequest extends AbstractJsonRequest
{
    /**
     * @var int
     * @Assert\Type(type="integer", message="centerId должно быть числом")
     * @Assert\NotBlank(message="Не указан центер")
     */
    public int $centerId;

    /**
     * @var string|null
     */
    public ?string $name = null;

    /**
     * @var string|null
     */
    public ?string $description = null;

    /**
     * @var string|null
     */
    public ?string $shortDescription = null;

    /**
     * @var string|null
     */
    public ?string $email = null;
}