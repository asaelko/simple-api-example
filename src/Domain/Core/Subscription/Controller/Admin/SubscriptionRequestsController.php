<?php

namespace App\Domain\Core\Subscription\Controller\Admin;

use App\Domain\Core\Subscription\Controller\Admin\Request\GetSubscriptionRequestsRequest;
use App\Domain\Core\Subscription\Controller\Admin\Response\SubscribeRequestResponse;
use App\Entity\PartnersMark;
use App\Entity\SubscriptionRequest;
use App\Repository\PartnersMarkRepository;
use CarlBundle\Response\Common\BooleanResponse;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriptionRequestsController extends AbstractController
{
    private PartnersMarkRepository $markRepository;

    public function __construct(
        PartnersMarkRepository $markRepository
    )
    {
        $this->markRepository = $markRepository;
    }

    /**
     * Список поданных заявок на подписку
     *
     * @OA\Get(operationId="/admin/subscription/request/list")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список запросов",
     *     @OA\JsonContent(
     *        @OA\Property(
     *            property="count",
     *            type="strig",
     *            example="20"
     *        ),
     *        @OA\Property(
     *        property="items",
     *        type="array",
     *          @OA\Items(
     *              ref=@DocModel(type=SubscribeRequestResponse::class)
     *          )
     *       )
     *     )
     * )
     *
     * @OA\Parameter(
     *     name="offset",
     *     in="query",
     *     description="Сдвиг от начала выборки",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Количество записей",
     *     @OA\Schema(type="integer")
     * )
     *
     * @OA\Tag(name="Admin\Subscription")
     * @param GetSubscriptionRequestsRequest $request
     *
     * @return JsonResponse
     */
    public function getRequests(GetSubscriptionRequestsRequest $request): JsonResponse
    {
        $result = $this
            ->getDoctrine()
            ->getRepository(SubscriptionRequest::class)
            ->list(
                $request->limit,
                $request->offset,
                $request->fromTime,
                $request->toTime
            )
        ;

        $marks = $this->markRepository->findBy([
            'partnerRequestClass' => SubscriptionRequest::class,
            'partnerRequestId' => array_map(static fn(SubscriptionRequest $r) => $r->getId(), $result['items'])
        ]);

        $subscriptionMarks = [];
        array_walk($marks, static function (PartnersMark $mark) use (&$subscriptionMarks) {
            $subscriptionMarks[$mark->getPartnerRequestId()] = $mark->getMark();
        });

        $items = array_map(
            static function (SubscriptionRequest $request) use ($subscriptionMarks) {
                return new SubscribeRequestResponse(
                    $request,
                    $subscriptionMarks[$request->getId()] ?? null
                );
            },
            $result['items']
        );

        return new JsonResponse(['items' => $items, 'count' => $result['count']]);
    }

    /**
     * Удалить заявку
     *
     * @OA\Delete(operationId="/admin/subscription/request/delete")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет результат запроса",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=BooleanResponse::class)
     *     )
     * )
     * @OA\Tag(name="Admin\Subscription")
     *
     * @param int $requestId
     *
     * @return BooleanResponse
     */
    public function deleteRequest(int $requestId): BooleanResponse
    {
        $repository = $this->getDoctrine()->getRepository(SubscriptionRequest::class);
        $request = $repository->find($requestId);
        if (!$request) {
            throw new NotFoundHttpException('Заявка не найдена');
        }

        $this->getDoctrine()->getManager()->remove($request);
        $this->getDoctrine()->getManager()->flush();

        return new BooleanResponse(true);
    }
}
