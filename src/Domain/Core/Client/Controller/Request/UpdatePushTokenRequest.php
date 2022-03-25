<?php

namespace App\Domain\Core\Client\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Запрос обновления пуш-токена клиента
 */
class UpdatePushTokenRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(nullable=true, example="c060a5aef9d6015ce9040fa9914eb37f14ce94cb88b33315524eca8a41a0eddb")
     * @Assert\Type(type="string", message="Токен должен быть передан строкой")
     */
    public ?string $token = null;

    /**
     * @OA\Property(nullable=true, example="ios")
     * @Assert\Type(type="string", message="Мобильная система должна быть передана строкой")
     */
    public ?string $mobileOs = null;

    /**
     * @OA\Property(nullable=true, example="iPhone13,1")
     * @Assert\Type(type="string", message="Модель телефона")
     */
    public ?string $phoneModel = null;
}
