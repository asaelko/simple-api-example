<?php

namespace App\Domain\Core\Purchase\Controller\Client\Request;

use App\Entity\Purchase\Purchase;
use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Запрос на создание запроса о покупке
 * @see Purchase
 */
class CreatePurchaseRequest extends AbstractJsonRequest
{
    /**
     * Фотография чека с подтверждением о покупке
     *
     * @var int
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank(message="Не указано фото чека")
     */
    public $receiptPhotoId;

    /**
     * Модель купленной машины
     *
     * @var int
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank(message="Не указана купленная модель автомобиля")
     */
    public $modelId;
}
