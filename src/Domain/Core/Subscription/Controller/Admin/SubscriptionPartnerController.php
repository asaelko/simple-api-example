<?php

namespace App\Domain\Core\Subscription\Controller\Admin;

use App\Domain\Core\Subscription\Controller\Admin\Factory\SubscriptionPartnerFactory;
use App\Domain\Core\Subscription\Controller\Admin\Request\CreateSubscriptionPartnerRequest;
use App\Domain\Core\Subscription\Controller\Admin\Request\ListSubscriptionPartnersRequest;
use App\Domain\Core\Subscription\Controller\Admin\Response\PartnerResponse;
use App\Entity\SubscriptionPartner;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriptionPartnerController extends AbstractController
{
    private SubscriptionPartnerFactory $factory;

    public function __construct(
        SubscriptionPartnerFactory $factory
    )
    {
        $this->factory = $factory;
    }

    /**
     * Список партнеров
     *
     * @OA\Get(operationId="/dashboard/admin/subscription/partner/list")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список партнеров",
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
     *              ref=@DocModel(type=PartnerResponse::class)
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
     * @param ListSubscriptionPartnersRequest $request
     *
     * @return JsonResponse
     */
    public function listPartners(ListSubscriptionPartnersRequest $request): JsonResponse
    {
        $result = $this
            ->getDoctrine()
            ->getRepository(SubscriptionPartner::class)
            ->list($request->limit, $request->offset);

        $items = array_map(
            static fn(SubscriptionPartner $partner) => new PartnerResponse($partner),
            $result['items']
        );

        return new JsonResponse(['items' => $items, 'count' => $result['count']]);
    }

    /**
     * Получение партнера
     *
     * @OA\Get(operationId="/dashboard/admin/subscription/partner/get")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет партнера c тачками",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=PartnerResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Subscription")
     * @param int $partnerId
     *
     * @return JsonResponse
     */
    public function getPartner(int $partnerId): JsonResponse
    {
        $partner = $this->getDoctrine()->getRepository(SubscriptionPartner::class)->find($partnerId);
        if (!$partner) {
            throw new NotFoundHttpException('Партнер не найден');
        }

        return new JsonResponse(new PartnerResponse($partner));
    }

    /**
     * Создание партнера
     *
     * @OA\Post(operationId="/dashboard/admin/subscription/partner/create")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет созданого партнера",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=PartnerResponse::class)
     *     )
     * )
     *
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@DocModel(type=CreateSubscriptionPartnerRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Subscription")
     * @param CreateSubscriptionPartnerRequest $request
     *
     * @return JsonResponse
     */
    public function createPartner(
        CreateSubscriptionPartnerRequest $request
    ): JsonResponse
    {
        $partner = new SubscriptionPartner();
        $partner = $this->factory->fillPartner($partner, $request);

        $this->getDoctrine()->getManager()->persist($partner);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(new PartnerResponse($partner));
    }

    /**
     * Обновление партнера
     *
     * @OA\Post(operationId="/dashboard/admin/subscription/partner/update")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет обновленого партнера",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=PartnerResponse::class)
     *     )
     * )
     *
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@DocModel(type=CreateSubscriptionPartnerRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Subscription")
     * @param CreateSubscriptionPartnerRequest $request
     * @param int                              $partnerId
     *
     * @return JsonResponse
     */
    public function updatePartner(
        CreateSubscriptionPartnerRequest $request,
        int $partnerId
    ): JsonResponse
    {
        $partner = $this->getDoctrine()->getRepository(SubscriptionPartner::class)->find($partnerId);
        if (!$partner) {
            throw new NotFoundHttpException('Партнер не найден');
        }
        $partner =  $this->factory->fillPartner($partner, $request);

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(new PartnerResponse($partner));
    }
}
