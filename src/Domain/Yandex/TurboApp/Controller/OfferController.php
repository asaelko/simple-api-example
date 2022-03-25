<?php

namespace App\Domain\Yandex\TurboApp\Controller;

use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Response\Common\BooleanResponse;
use DealerBundle\Request\NewAnonymousDriveOfferRequest;
use DealerBundle\Service\DriveOfferService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class OfferController extends AbstractController
{
    private DriveOfferService $driveOfferService;
    private LoggerInterface $logger;

    public function __construct(
        DriveOfferService $driveOfferService,
        LoggerInterface $offerLogger
    )
    {
        $this->driveOfferService = $driveOfferService;
        $this->logger = $offerLogger;
    }

    /**
     * Отправка заявления на КП по дилерской машине
     *
     * @OA\Post(operationId="yandex/offer/request")
     *
     * @OA\Response(
     *     response=200,
     *     description="Результат запроса КП",
     *     @OA\JsonContent(
     *          @OA\Property(property="result", type="bool", example=true, description="Флаг успешности запроса оффера")
     *     )
     * )
     *
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@Model(type=NewAnonymousDriveOfferRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Yandex\TurboApp")
     *
     * @param NewAnonymousDriveOfferRequest $offerRequest
     * @param int                           $carId
     *
     * @return JsonResponse
     */
    public function requestAction(NewAnonymousDriveOfferRequest $offerRequest, int $carId): JsonResponse
    {
        try {
            $this->driveOfferService->requestAnonOfferFromDealer($offerRequest, $carId, 'yandex');
        } catch (Throwable $Ex) {
            if ($Ex instanceof InvalidValueException) {
                throw $Ex;
            }

            $this->logger->error($Ex);
            return new JsonResponse(new BooleanResponse(false));
        }

        return new JsonResponse(new BooleanResponse(true));
    }
}
