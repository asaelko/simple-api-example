<?php

namespace App\Domain\Infrastructure\IpTelephony\Uis\Controller;

use App\Domain\Infrastructure\IpTelephony\Uis\Request\MakeClientCallRequest;
use App\Domain\Infrastructure\IpTelephony\Uis\Request\MakeDriverCallRequest;
use App\Domain\Infrastructure\IpTelephony\Uis\Request\ProcessingCallActionRequest;
use App\Domain\Infrastructure\IpTelephony\Uis\Service\UisService;
use App\Domain\Notifications\Messages\Call\Message\CheckCallStatusMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use CarlBundle\Exception\RestException;
use CarlBundle\Response\Common\BooleanResponse;
use CarlBundle\Service\UserService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class UisController extends AbstractController
{
    /**
     * Позвонить клиенту
     *
     * Метод принимает идентификатор поездки
     * @OA\Post(operationId="driver/call")
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@Model(type=MakeDriverCallRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет статус",
     *     @OA\JsonContent(
     *            ref=@Model(type=BooleanResponse::class)
     *    )
     * )
     *
     * @OA\Response(
     *     response=404, description="Поездка не найдена",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Поездка не найдена")
     *     )
     * )
     *
     *
     * @OA\Tag(name="Driver\Call")
     * @param MakeDriverCallRequest $request
     * @param UisService $service
     * @return BooleanResponse
     * @throws RestException
     */
    public function makeDriverCallAction(MakeDriverCallRequest $request, UisService $service): BooleanResponse
    {
        $drive = $this->getDoctrine()->getRepository(Drive::class)->find($request->driveId);

        if (!$drive) {
            throw new NotFoundHttpException('Поездка не найдена');
        }

        if (in_array($drive->getState(), Drive::$finishedStates)) {
            throw new RestException('Поездка уже завершена');
        }

        return new BooleanResponse((bool) $service->createCallToUser($drive));
    }

    /**
     * Позвонить клиенту по его просьбе (обратный звонок)
     *
     * Метод принимает номер куда надо набрать
     * @OA\Post(operationId="/call/from-client")
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@Model(type=MakeClientCallRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет статус",
     *     @OA\JsonContent(
     *            ref=@Model(type=BooleanResponse::class)
     *    )
     * )
     *
     * @OA\Tag(name="System\Call")
     * @param MakeClientCallRequest $request
     * @param UisService $service
     * @param UserService $userService
     * @return BooleanResponse
     */
    public function makeClientCall(
        MakeClientCallRequest $request,
        UisService $service,
        UserService $userService
    ): BooleanResponse
    {
        /** @var Client|null $user */
        $client = $userService->resolveClient($request->phone);

        if (!$client || $request->toCallCanter) {
            return new BooleanResponse((bool) $service->makeCallToCallCenter($request->phone));
        }

        $drive = $client->getUnfinishedDrives();
        if ($drive->isEmpty()) {
            return new BooleanResponse((bool) $service->makeCallToCallCenter($request->phone));
        }

        $callSessionId = $service->createCallToUser($drive->first());
        if ($callSessionId) {
            $this
                ->dispatchMessage(
                    new CheckCallStatusMessage($drive->first()->getId(), $callSessionId),
                    [new DelayStamp(600 * 1000)]
                );
            $status = true;
        } else {
            $status = false;
        }

        return new BooleanResponse($status);
    }

    /**
     * Сохранить историю звонка
     *
     * @OA\Post(operationId="/register/call")
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@Model(type=ProcessingCallActionRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет статус",
     *     @OA\JsonContent(
     *            ref=@Model(type=BooleanResponse::class)
     *    )
     * )
     *
     * @OA\Tag(name="System\Call")
     * @param ProcessingCallActionRequest $request
     * @param UisService                  $service
     * @param UserService                 $userService
     *
     * @return BooleanResponse
     */
    public function registerCallAction(
        ProcessingCallActionRequest $request,
        UisService $service,
        UserService $userService
    ): BooleanResponse
    {
        $client = $userService->resolveAnonymousClient($request->client_phone);
        $status = $service->processingCallEntity($request, $client);
        return new BooleanResponse($status);
    }
}