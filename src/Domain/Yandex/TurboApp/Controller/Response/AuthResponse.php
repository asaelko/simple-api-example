<?php

namespace App\Domain\Yandex\TurboApp\Controller\Response;

use App\Domain\Core\Client\Controller\Response\ClientAuthApiResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class AuthResponse
{
    /**
     * @OA\Property(description="Идентификационный токен пользователя в сервисе CARL", example="6be90620-cf5d-4746-8e9e-93383d75f546")
     */
    public string $token;

    /**
     * @OA\Property(description="Данные клиента для отображения их в яндекс.турбо", ref=@Model(type=ClientAuthApiResponse::class))
     */
    public ClientAuthApiResponse $client;

    public function __construct(string $token, ClientAuthApiResponse $client)
    {
        $this->token = $token;
        $this->client = $client;
    }
}
