<?php

namespace App\Domain\Core\Dashboard\Controller\Schedule;

use App\Domain\Core\Dashboard\Response\Schedule\ScheduleSubscriptionResponse;
use App\Domain\Core\Model\Service\ScheduleSubscriber;
use App\Entity\Model\ScheduleNotification;
use DateTime;
use Exception;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ScheduleSubscriberController extends AbstractController
{
    private ScheduleSubscriber $scheduleSubscriber;

    public function __construct(
        ScheduleSubscriber $scheduleSubscriber
    )
    {
        $this->scheduleSubscriber = $scheduleSubscriber;
    }

    /**
     * Получить количество запросов на появление расписания по моделям за заданный промежуток времени
     *
     * @OA\Get(
     *     operationId="dashboard/schedule/get_subscriptions_by_time_range"
     * )
     *
     * @OA\Parameter(
     *     name="start",
     *     in="query",
     *     required=true,
     *     description="дата начала, timestamp",
     *     @OA\Schema(type="integer")
     * )
     *
     * @OA\Parameter(
     *     name="end",
     *     in="query",
     *     required=true,
     *     description="дата конца, timestamp",
     *     @OA\Schema(type="integer")
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Количество подписок по дням и моделям",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="model", properties={
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="Porche Cayenne")
     *                  }),
     *                  @OA\Property(property="subscriptions", type="array",
     *                      @OA\Items(type="integer")
     *                  )
     *              )
     *         )
     *     )
     * )
     *
     * @OA\Tag(name="Dashboard\Schedule")
     *
     * @param Request $request
     *
     * @return array
     * @throws Exception
     */
    public function getSubscriptionsForTimeRange(Request $request): array
    {
        $subscriptionsCounts = $this->scheduleSubscriber->getSubscriptionsCountByTimeRange(
            (new DateTime)->setTimestamp($request->get('start')),
            (new DateTime)->setTimestamp($request->get('end')),
        );

        $subscriptions = [];

        foreach($subscriptionsCounts as $subscriptionStringByDate) {
            $subscriptions[$subscriptionStringByDate['id']] ??= [];
            $subscriptions[$subscriptionStringByDate['id']]['model'] = ['id' => $subscriptionStringByDate['id'], 'name' => $subscriptionStringByDate['name']];

            $subscriptions[$subscriptionStringByDate['id']]['subscriptions'] ??= [];
            $subscriptions[$subscriptionStringByDate['id']]['subscriptions'][$subscriptionStringByDate['date']] = $subscriptionStringByDate['notificationsCount'];
        }

        return array_values($subscriptions);
    }

    /**
     * Получить все подписки на расписания на конкретное время
     *
     * @OA\Get(
     *     operationId="dashboard/schedule/subscriptions"
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     required=false,
     *     description="количество элементов в выборке, int",
     *     @OA\Schema(type="integer")
     * )
     *
     * @OA\Parameter(
     *     name="offset",
     *     in="query",
     *     required=false,
     *     description="смещение относительно начала выборки, int",
     *     @OA\Schema(type="integer")
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Подписки на появление расписания в конкретный день",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="items",
     *              type="array",
     *              @OA\Items(ref=@Model(type=ScheduleSubscriptionResponse::class))
     *          ),
     *          @OA\Property(
     *              property="count",
     *              type="integer",
     *              example=10
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Dashboard\Schedule")
     *
     * @param Request $request
     *
     * @return array
     * @throws Exception
     */
    public function getSubscriptions(Request $request): array
    {
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);

        $subscriptions = $this->scheduleSubscriber->getSubscriptionsWithRequestedTime($limit, $offset);

        $subscriptions['items'] = array_map(
            static fn(ScheduleNotification $notification) => new ScheduleSubscriptionResponse($notification),
            $subscriptions['items']
        );

        return $subscriptions;
    }
}
