<?php

namespace App\Domain\Yandex\TurboApp\Controller;

use App\Domain\Yandex\TurboApp\Controller\Request\BookingRequest;
use App\Domain\Yandex\TurboApp\Controller\Response\BookingResultResponse;
use App\Domain\Yandex\TurboApp\Service\TurboAppService;
use CarlBundle\Entity\Client;
use CarlBundle\Exception\RestException;
use CarlBundle\Service\Drive\Exception\ServerPaymentRequiredException;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use WidgetBundle\Response\v3\ServerPaymentRequiredResponse;

class DriveController extends AbstractController
{
    private TurboAppService $turboAppService;

    public function __construct(TurboAppService $turboAppService)
    {
        $this->turboAppService = $turboAppService;
    }

    /**
     * Бронирование тест-драйва в TurboApp
     *
     * @OA\Post(operationId="yandex/book")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет поездку и ссылку на оплату если надо",
     *     @OA\JsonContent(
     *          ref=@Model(type=BookingResultResponse::class)
     *     )
     * )
     *
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@Model(type=BookingRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Yandex\TurboApp")
     *
     * @param BookingRequest $request
     *
     * @return JsonResponse
     * @throws RestException
     */
    public function bookTestDrive(BookingRequest $request): JsonResponse
    {
        $client = $this->getUser();
        if (!($client instanceof Client)) {
            throw new AccessDeniedHttpException('Необходимо войти в приложение для бронирования тест-драйва');
        }
        try {
            $result = $this->turboAppService->bookDrive($request, $client);
            $response = new BookingResultResponse($result->getClient()->getId(), $result->getId());
        } catch (ServerPaymentRequiredException $ex) {
            $response = new ServerPaymentRequiredResponse(
                false,
                $ex->getCode(),
                $ex->getClientId(),
                $ex->getRedirectUrl(),
                $ex->getTransactionId(),
                $ex->getSessionId()
            );
        }

        return new JsonResponse($response);
    }

    /**
     * Обработка платежной нотификации от яндекса
     *
     * @OA\Post(operationId="yandex/payment/notify")
     *
     * @OA\Response(
     *     response=200
     * )
     *
     * @OA\Tag(name="Yandex\TurboApp")
     */
    public function processPayment(Request $request): Response
    {
        $this->turboAppService->processPayment($request->getContent());

        return new Response();
    }
}
