<?php

namespace App\Domain\Core\Drive\Controller;

use App\Domain\Core\Drive\Controller\Request\UpdateFeedbackRequest;
use App\Domain\Core\Drive\Service\FeedbackHandler;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use CarlBundle\Exception\ValueAlreadyUsedException;
use CarlBundle\Service\DriveService;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClientController extends AbstractController
{
    private DriveService $driveService;
    private FeedbackHandler $feedbackHandler;

    public function __construct(
        DriveService $driveService,
        FeedbackHandler $feedbackHandler
    )
    {
        $this->driveService = $driveService;
        $this->feedbackHandler = $feedbackHandler;
    }

    /**
     * @OA\Get(
     *     operationId="client/drives/list"
     * )
     *
     * @Rest\View(
     *     serializerGroups={
     *          "car_view", "car_equipment",
     *          "equipment_view", "equipment_model",
     *          "model_view", "model_brand",
     *          "brand_view",
     *          "drive_view",
     *          "photo_view",
     *          "client_view",
     *          "client_car_view",
     *          "client_with_car_view",
     *          "audio_short_view",
     *          "dealer_view",
     *          "city_view"
     * },
     *     statusCode=200
     * )
     *
     * @OA\Tag(name="Client\Drive")
     *
     * @param Request $Request
     *
     * @return array
     */
    public function listAction(Request $Request): array
    {
        $client = $this->getUser();
        assert($client instanceof Client);
        return $this->driveService->getDrivesForClient($client, $Request);
    }

    /**
     * Отправка пользователем фидбека по поездке
     *
     * Словари для значений в массивах можно получить по методу /dictionary/feedback
     *
     * @OA\Post(
     *     operationId="client/drive/feedback",
     *     @OA\RequestBody(
     *          @Model(type=UpdateFeedbackRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200, description="Фидбек успешно установлен",
     *     @OA\JsonContent(
     *        @OA\Property(property="result", type="bool", example=true)
     *     )
     * )
     *
     * @OA\Response(
     *     response=478, description="Фидбек уже был установлен",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string")
     *     )
     * )
     *
     * @OA\Response(
     *     response=404, description="Поездка не найдена",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string")
     *     )
     * )
     *
     * @OA\Response(
     *     response=401, description="Доступ к редактированию запрещен",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string")
     *     )
     * )
     *
     * @OA\Tag(name="Client\Drive\Feedback")
     *
     * @param int                   $driveId
     * @param UpdateFeedbackRequest $request
     *
     * @return JsonResponse
     * @throws ValueAlreadyUsedException
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
     */
    public function setFeedbackAction(int $driveId, UpdateFeedbackRequest $request): JsonResponse
    {
        $drive = $this->getDoctrine()->getRepository(Drive::class)->find($driveId);
        if (!$drive) {
            throw new NotFoundHttpException('Поездка не найдена');
        }

        $client = $this->getUser();
        assert($client instanceof Client);

        if ($client->getId() !== $drive->getClient()->getId()) {
            throw new AccessDeniedHttpException('Доступ запрещен');
        }

        $this->feedbackHandler->applyFeedback($drive, $request);
        $drive->setState(Drive::STATE_COMPLETED);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['result' => true]);
    }
}
