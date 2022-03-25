<?php

namespace App\Domain\Notifications\MassPush\Controller;

use App\Domain\Notifications\MassPush\Controller\Request\NewMassPushRequest;
use App\Domain\Notifications\MassPush\Controller\Response\MassPushNotificationResponse;
use App\Domain\Notifications\MassPush\Exception\NonUniqueMassPushException;
use App\Domain\Notifications\MassPush\Exception\TooLateToCancelMassPushException;
use App\Domain\Notifications\MassPush\MassPushManager;
use App\Entity\Notifications\MassPush\MassPushNotification;
use CarlBundle\Response\Common\IterableListResponse;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Контроллер управления масспушами
 */
class MassPushController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private MassPushManager $massPushManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        MassPushManager $massPushManager
    )
    {
        $this->massPushManager = $massPushManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Получаем список всех созданных рассылок, отсортированных в порядке убывания даты отправки
     *
     * @OA\Get(
     *     operationId="admin/mass-pushes/list"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив масспушей",
     *     @OA\JsonContent(
     *        @OA\Property(
     *            property="count",
     *            type="integer",
     *            example=20
     *        ),
     *        @OA\Property(
     *        property="items",
     *        type="array",
     *          @OA\Items(
     *              ref=@Model(type=MassPushNotificationResponse::class)
     *          )
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Mass push")
     *
     * @return JsonResponse
     */
    public function listAction(): JsonResponse
    {
        $notifications = $this->entityManager->getRepository(MassPushNotification::class)->findAll();

        return new JsonResponse(new IterableListResponse(
            array_map(static function (MassPushNotification $notification) {
                return new MassPushNotificationResponse($notification);
            }, $notifications),
            count($notifications),
        ));
    }

    /**
     * Создает новое событие масспуша
     *
     * Вернет ошибку в том случае, если на этот день рассылка масспуша уже запланирована
     *
     * @OA\Post(
     *     operationId="admin/mass-pushes/create",
     *     @OA\RequestBody(
     *          @Model(type=NewMassPushRequest::class)
     *     )
     * )
     *
     * @Security("is_granted('ROLE_ADMIN_USER')")
     *
     * @OA\Response(
     *     response=200, description="Вернёт созданный масспуш",
     *     @Model(type=MassPushNotificationResponse::class)
     * )
     *
     * @OA\Response(
     *     response=492, description="В этот день уже есть рассылка масспуша",
     *     @OA\JsonContent(
     *          @OA\Property(property="error", type="string", example="В этот день уже есть рассылка масспуша")
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Mass push")
     *
     * @param NewMassPushRequest $newMassPushRequest
     * @return JsonResponse
     * @throws NonUniqueMassPushException
     */
    public function createAction(NewMassPushRequest $newMassPushRequest): JsonResponse
    {
        $notification = $this->massPushManager->create($newMassPushRequest);

        return new JsonResponse(
            new MassPushNotificationResponse($notification)
        );
    }

    /**
     * Редактирует существующую рассылку масспуша
     *
     * Вернет ошибку в том случае, если на этот день рассылка масспуша уже запланирована, или до рассылки осталось несколько секунд
     *
     * @OA\Post(
     *     operationId="admin/mass-pushes/edit",
     *     @OA\RequestBody(
     *          @Model(type=NewMassPushRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200, description="Вернёт отредактированный масспуш",
     *     @Model(type=MassPushNotificationResponse::class)
     * )
     *
     * @OA\Response(
     *     response=477, description="Слишком поздно редактировать масспуш, отправка вот-вот начнется",
     *     @OA\JsonContent(
     *          @OA\Property(property="error", type="string", example="Слишком поздно редактировать масспуш, отправка вот-вот начнется")
     *     )
     * )
     *
     * @OA\Response(
     *     response=492, description="В этот день уже есть рассылка масспуша",
     *     @OA\JsonContent(
     *          @OA\Property(property="error", type="string", example="В этот день уже есть рассылка масспуша" )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Mass push")
     *
     * @param NewMassPushRequest $massPushRequest
     * @param int $notificationId
     * @return JsonResponse
     * @throws TooLateToCancelMassPushException
     * @throws NonUniqueMassPushException
     */
    public function editAction(NewMassPushRequest $massPushRequest, int $notificationId): JsonResponse
    {
        $massPushNotification = $this->entityManager
            ->getRepository(MassPushNotification::class)->find($notificationId);

        if (!$massPushNotification) {
            throw new NotFoundHttpException('Рассылка не найдена');
        }

        $massPushNotification = $this->massPushManager->edit($massPushNotification, $massPushRequest);

        return new JsonResponse(
            new MassPushNotificationResponse($massPushNotification)
        );
    }

    /**
     * Отменяет рассылку масспуша
     *
     * @OA\Delete(
     *     operationId="admin/mass-pushes/cancel"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт отмененный масспуш",
     *     @Model(type=MassPushNotificationResponse::class)
     * )
     *
     * @OA\Tag(name="Admin\Mass push")
     *
     * @param int $notificationId
     * @return JsonResponse
     */
    public function cancelAction(int $notificationId): JsonResponse
    {
        $massPushNotification = $this->entityManager
            ->getRepository(MassPushNotification::class)->find($notificationId);

        if (!$massPushNotification) {
            throw new NotFoundHttpException('Рассылка не найдена');
        }

        $massPushNotification = $this->massPushManager->cancel($massPushNotification);

        return new JsonResponse(
            new MassPushNotificationResponse($massPushNotification)
        );
    }
}
