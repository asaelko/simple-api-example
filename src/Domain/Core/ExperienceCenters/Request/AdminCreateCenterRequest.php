<?php


namespace App\Domain\Core\ExperienceCenters\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class AdminCreateCenterRequest extends AbstractJsonRequest
{
    /**
     * @var int
     * @Assert\Type(type="integer", message="brandId должно быть числом")
     * @Assert\NotBlank(message="Не указан брнед")
     */
    public int $brandId;

    /**
     * @var string
     * @Assert\Type(type="string", message="Название центра долдно быть строкой")
     * @Assert\NotBlank(message="Не указано название уентра")
     */
    public string $name;

    /**
     * @var string
     * @Assert\Type(type="string", message="Описниае центра должно быть строкой")
     * @Assert\NotBlank(message="Не указано описание центра")
     */
    public string $description;

    /**
     * @var string
     * @Assert\Type(type="string", message="Короткое описание центра должно быть строкой")
     * @Assert\NotBlank(message="Не указано короткое описание центра")
     */
    public string $shortDescription;

    /**
     * @var string
     * @Assert\Type(type="string", message="Не верный формат почты")
     * @Assert\NotBlank(message="Не указана почта для уведомлений")
     */
    public string $email;

    /**
     * @var string|null
     */
    public ?string $organizationName = null;
}