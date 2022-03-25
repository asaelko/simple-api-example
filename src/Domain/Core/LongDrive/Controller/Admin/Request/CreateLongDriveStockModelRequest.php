<?php

namespace App\Domain\Core\LongDrive\Controller\Admin\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class CreateLongDriveStockModelRequest extends AbstractJsonRequest
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
     * Массив цен, где ключ массива - день, с которого начинает действовать цена, а значение - цена
     *
     * @var array
     *
     * @Assert\NotBlank
     * @OA\Property(type="object")
     */
    public $prices;

    /**
     * @var string|null
     *
     * @Assert\Type("string")
     */
    public $description;

    /**
     * @var int|null
     *
     * @Assert\Type("integer")
     */
    public $equipmentId;

    /**
     * @var int|null
     *
     * @Assert\Type("integer")
     */
    public $modificationId;
}