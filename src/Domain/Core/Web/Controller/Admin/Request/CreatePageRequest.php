<?php

namespace App\Domain\Core\Web\Controller\Admin\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class CreatePageRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(default="Главная страница")
     * @Assert\NotBlank(message="Название страницы не может быть пустым")
     */
    public string $name;

    /** @OA\Property(default="CARL - самый лучший сервис") */
    public ?string $title = null;

    /** @OA\Property(default="Покупайте, бронируйте, лонгдрайвите и все такое через CARL") */
    public ?string $description = null;

    /** @OA\Property(default="const i = 0;") */
    public ?string $embed = null;
}