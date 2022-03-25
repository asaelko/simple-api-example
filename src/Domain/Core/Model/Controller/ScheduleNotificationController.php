<?php

namespace App\Domain\Core\Model\Controller;

use App\Domain\Core\Model\Repository\ModelRepository;
use App\Domain\Core\Model\Service\ScheduleSubscriber;
use App\Entity\Model\ScheduleNotification;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Schedule;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Request\ScheduleSubscription\ScheduleSubscriptionRequest;
use CarlBundle\Service\ScheduleService;
use DateTime;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * API-методы для работы с подписками на расписание
 */
class ScheduleNotificationController extends AbstractController
{
    private ScheduleSubscriber $scheduleSubscriber;
    private ModelRepository $modelRepository;
    private ScheduleService $scheduleService;

    public function __construct(
        ScheduleSubscriber $scheduleSubscriber,
        ModelRepository $modelRepository,
        ScheduleService $scheduleService
    )
    {
        $this->scheduleSubscriber = $scheduleSubscriber;
        $this->modelRepository = $modelRepository;
        $this->scheduleService = $scheduleService;
    }

    /**
     * Отдаем статус подписки на появление расписания по модели
     *
     * @OA\Get(operationId="schedule/subscription/get")
     *
     * @OA\Response(
     *     response=200,
     *     description="",
     *     @OA\JsonContent(
     *          @OA\Property(property="result", type="bool", example=false)
     *     )
     * )
     *
     * @OA\Response(response=404, description="Модель не найдена")
     *
     * @OA\Tag(name="Client\Schedule\Notifications")
     *
     * @param int $modelId
     * @return JsonResponse
     */
    public function getSubscription(int $modelId): JsonResponse
    {
        $client = $this->getUser();
        assert($client instanceof Client);

        $model = $this->modelRepository->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        return new JsonResponse(['result' => (bool) $this->scheduleSubscriber->getSubscription($client, $model)]);
    }

    /**
     * Отдаем все подписки клиента на появление расписания
     *
     * @OA\Get(operationId="schedule/subscription/all")
     *
     * @OA\Response(
     *     response=200,
     *     description="",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="models",
     *              type="array",
     *              @OA\Items(@OA\Property(type="int", example=1))
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Client\Schedule\Notifications")
     *
     * @return JsonResponse
     */
    public function getAllSubscriptions(): JsonResponse
    {
        $client = $this->getUser();
        assert($client instanceof Client);

        $subscriptions = $this->scheduleSubscriber->getClientSubscriptions($client);

        return new JsonResponse([
            'models' => array_map(
                static fn (ScheduleNotification $notification) => $notification->getModel() ? $notification->getModel()->getId() : null,
                $subscriptions
            )
        ]);
    }

    /**
     * Подписывает клиента на появление расписания
     *
     * @OA\Post(
     *     operationId="schedule/subscription/subscribe",
     *     @OA\RequestBody(
     *         @Model(type=ScheduleSubscriptionRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="",
     *     @OA\JsonContent(
     *          @OA\Property(property="result", type="bool", example=true)
     *     )
     * )
     *
     * @OA\Response(response=404, description="Модель не найдена")
     *
     * @OA\Tag(name="Client\Schedule\Notifications")
     *
     * @param int                         $modelId
     * @param ScheduleSubscriptionRequest $request
     *
     * @return JsonResponse
     */
    public function addSubscription(int $modelId, ScheduleSubscriptionRequest $request): JsonResponse
    {
        $client = $this->getUser();
        assert($client instanceof Client);

        $model = $this->modelRepository->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        $requestedTime = null;
        if ($request->requestedTime) {
            $requestedTime = (new DateTime())->setTimestamp($request->requestedTime);
        }

        return new JsonResponse(['result' => (bool) $this->scheduleSubscriber->createSubscription($client, $model, $requestedTime)]);
    }

    /**
     * Удаляет подписку клиента на появление расписания
     *
     * @OA\Delete(operationId="schedule/subscription/remove")
     *
     * @OA\Response(
     *     response=200,
     *     description="",
     *     @OA\JsonContent(
     *          @OA\Property(property="result", type="bool", example=true)
     *     )
     * )
     *
     * @OA\Response(response=404, description="Модель не найдена")
     *
     * @OA\Tag(name="Client\Schedule\Notifications")
     *
     * @param int $modelId
     * @return JsonResponse
     */
    public function removeSubscription(int $modelId): JsonResponse
    {
        $client = $this->getUser();
        assert($client instanceof Client);

        $model = $this->modelRepository->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        return new JsonResponse(['result' => $this->scheduleSubscriber->deleteSubscription($client, $model)]);
    }

    /**
     * Получить слоты для подписки на расписание в выбранный день
     *
     * @OA\Get(operationId="schedule/subscriptions/timeslots")
     *
     * @OA\Response(
     *     response=200,
     *     description="Доступные слоты для подписки на расписание",
     *     @OA\JsonContent(
     *          @OA\Property(type="array", @OA\Items(
     *              @OA\Property(property="start", type="integer", example="1625554800"),
     *              @OA\Property(property="id", type="integer", example="1")
     *          ))
     *     )
     * )
     *
     * @OA\Tag(name="Client\Schedule\Notifications")
     *
     * @return array
     * @throws InvalidValueException
     */
    public function getSlotsForSubscription(): array
    {
        $availableScheduleSlots = [];
        $dateStart = new DateTime('tomorrow');
        $slots = [];
        for ($i = 0; $i < 10; $i++) {
            $dateStart->setTime(7, 00, 00);
            if (isset($availableScheduleSlots[$dateStart->format('dmY')])) {
                $dateStart->modify('+1 day');
                continue;
            }
            for ($j = 0; $j <= 10; $j++) {
                $slots[] = ['start' => clone $dateStart, 'id' => $i + 1];
                $dateStart->modify('+1 hour');
            }
            $dateStart->modify('+1 day');
        }

        return $slots;
    }

    /**
     * Получить слоты для подписки на расписание модели в выбранный день
     *
     * @OA\Get(operationId="schedule/subscriptions/model/timeslots")
     *
     * @OA\Response(
     *     response=200,
     *     description="Доступные слоты для подписки на расписание",
     *     @OA\JsonContent(
     *          @OA\Property(type="array", @OA\Items(
     *              @OA\Property(property="start", type="integer", example="1625554800"),
     *              @OA\Property(property="id", type="integer", example="1")
     *          ))
     *     )
     * )
     *
     * @OA\Tag(name="Client\Schedule\Notifications")
     *
     * @param int|null $modelId
     *
     * @return array
     * @throws InvalidValueException
     */
    public function getSlotsForSubscriptionForModel(int $modelId = null): array
    {
        $availableScheduleSlots = [];
        $model = $this->modelRepository->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException('Модель не найдена');
        }
        if ($model->getActiveCar()) {
            $availableScheduleSlots = $this->scheduleService->getCarSchedules(
                $model->getActiveCar()->getId(),
                (new DateTime('tomorrow'))->setTime(7, 0, 0)->getTimestamp(),
                (new DateTime('+10 days'))->setTime(17, 0, 0)->getTimestamp(),
            );
            $availableScheduleSlots = array_flip(array_map(
                static fn(Schedule $schedule) => $schedule->getStart()->format('dmY'),
                $availableScheduleSlots
            ));
        }

        $dateStart = new DateTime('tomorrow');
        $slots = [];
        for ($i = 0; $i < 10; $i++) {
            $dateStart->setTime(7, 00, 00);
            if (isset($availableScheduleSlots[$dateStart->format('dmY')])) {
                $dateStart->modify('+1 day');
                continue;
            }
            for ($j = 0; $j <= 10; $j++) {
                $slots[] = ['start' => clone $dateStart, 'id' => $i + 1];
                $dateStart->modify('+1 hour');
            }
            $dateStart->modify('+1 day');
        }

        return $slots;
    }
}
