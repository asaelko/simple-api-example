<?php

namespace App\Domain\Core\Equipment\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class NewEquipmentMediaRequest extends AbstractJsonRequest
{
    /**
     * @var int
     *
     * ID медиаконтента, который необходимо прикрепить
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     */
    public $mediaId;

    /**
     * @var string
     *
     * Категория, к которой контент относится
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     */
    public $category;
}