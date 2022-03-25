<?php

namespace App\Domain\Yandex\TurboApp\Controller;

use App\Domain\Core\Client\Controller\Response\ClientAuthApiResponse;
use App\Domain\Yandex\TurboApp\Exception\NoAuthDataException;
use App\Domain\Yandex\TurboApp\Service\TurboAppService;
use CarlBundle\Exception\ClientIsBanLoginException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Domain\Yandex\TurboApp\Controller\Request\OauthRequest;
use App\Domain\Yandex\TurboApp\Controller\Response\AuthResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class AuthController extends AbstractController
{
    private TurboAppService $turboAppService;

    public function __construct(
        TurboAppService $turboAppService
    )
    {
        $this->turboAppService = $turboAppService;
    }

    /**
     * Авторизация пользователя в TurboApp
     *
     * @OA\Post(operationId="yandex/auth")
     *
     * @OA\RequestBody(
     *     @Model(type=OauthRequest::class)
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Данные авторизованного пользователя",
     *     @Model(type=AuthResponse::class)
     * )
     *
     * @OA\Response(
     *     response=523,
     *     description="Не смогли получить авторизационные данные от сервиса Яндекса"
     * )
     *
     * @OA\Response(
     *     response=530,
     *     description="Клиент заблокирован в сервисе"
     * )
     *
     * @OA\Tag(name="Yandex\TurboApp")
     *
     * @param OauthRequest $request
     * @return JsonResponse
     * @throws ClientIsBanLoginException
     * @throws NoAuthDataException
     */
    public function auth(OauthRequest $request): JsonResponse
    {
        $client = $this->turboAppService->authUserByYandexToken($request->token);

        return new JsonResponse(new AuthResponse($client->getToken(), new ClientAuthApiResponse($client, $client->getCity())));
    }
}
