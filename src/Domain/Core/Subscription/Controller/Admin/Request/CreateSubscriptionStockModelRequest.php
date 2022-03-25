<?php

namespace App\Domain\Core\Subscription\Controller\Admin\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class CreateSubscriptionStockModelRequest extends AbstractJsonRequest
{
    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\Type("integer")
     */
    public $modelId;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\Type("integer")
     */
    public $partnerId;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Type("string")
     */
    public $parnerCode;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\Type("integer")
     */
    public $price;

    /**
     * Массив описаний в формате "ключ" - "значение" а ля "Цвет": "Белый"
     *
     * @var array
     *
     * @Assert\NotBlank
     * @OA\Property(type="object")
     */
    public $options = [];

    /**
     * @var string|null
     *
     * @Assert\Type("string")
     */
    public $description;

    /**
     * @var string|null
     *
     * @Assert\Type("string")
     */
    public $eqipmentUrl;
}