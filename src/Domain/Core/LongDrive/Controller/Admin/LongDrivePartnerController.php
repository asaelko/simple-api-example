<?php

namespace App\Domain\Core\LongDrive\Controller\Admin;

use App\Domain\Core\LongDrive\Controller\Admin\Factory\LongDrivePartnerFactory;
use App\Domain\Core\LongDrive\Controller\Admin\Request\CreateLongDrivePartnerRequest;
use App\Domain\Core\LongDrive\Controller\Admin\Request\ListLongDrivePartnersRequest;
use App\Domain\Core\LongDrive\Controller\Admin\Response\PartnerResponse;
use App\Entity\LongDrive\LongDrivePartner;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LongDrivePartnerController extends AbstractController
{
    private LongDrivePartnerFactory $factory;

    public function __construct(
        LongDrivePartnerFactory $factory
    )
    {
        $this->factory = $factory;
    }

    /**
     * Список партнеров лонг-драйва
     *
     * @OA\Get(operationId="/dashboard/admin/long-drive/partner/list")
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
     * @OA\Tag(name="Admin\LongDrive")
     * @param ListLongDrivePartnersRequest $request
     *
     * @return JsonResponse
     */
    public function listPartners(ListLongDrivePartnersRequest $request): JsonResponse
    {
        $result = $this
            ->getDoctrine()
            ->getRepository(LongDrivePartner::class)
            ->list($request->limit, $request->offset);

        $items = array_map(
            static fn(LongDrivePartner $partner) => new PartnerResponse($partner),
            $result['items']
        );

        return new JsonResponse(['items' => $items, 'count' => $result['count']]);
    }

    /**
     * Получение партнера по лонг-драйву
     *
     * @OA\Get(operationId="/dashboard/admin/long-drive/partner/get")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет партнера c тачками",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=PartnerResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Admin\LongDrive")
     * @param int $partnerId
     *
     * @return JsonResponse
     */
    public function getPartner(int $partnerId): JsonResponse
    {
        $partner = $this->getDoctrine()->getRepository(LongDrivePartner::class)->find($partnerId);
        if (!$partner) {
            throw new NotFoundHttpException('Партнер не найден');
        }

        return new JsonResponse(new PartnerResponse($partner));
    }

    /**
     * Создание партнера
     *
     * @OA\Post(operationId="/dashboard/admin/long-drive/partner/create")
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
     *              ref=@DocModel(type=CreateLongDrivePartnerRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\LongDrive")
     * @param CreateLongDrivePartnerRequest $request
     *
     * @return JsonResponse
     */
    public function createPartner(
        CreateLongDrivePartnerRequest $request
    ): JsonResponse
    {
        $partner = new LongDrivePartner();
        $partner = $this->factory->fillPartner($partner, $request);

        $this->getDoctrine()->getManager()->persist($partner);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(new PartnerResponse($partner));
    }

    /**
     * Обновление партнера
     *
     * @OA\Post(operationId="/dashboard/admin/long-drive/partner/update")
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
     *              ref=@DocModel(type=CreateLongDrivePartnerRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\LongDrive")
     * @param CreateLongDrivePartnerRequest $request
     * @param int                              $partnerId
     *
     * @return JsonResponse
     */
    public function updatePartner(
        CreateLongDrivePartnerRequest $request,
        int $partnerId
    ): JsonResponse
    {
        $partner = $this->getDoctrine()->getRepository(LongDrivePartner::class)->find($partnerId);
        if (!$partner) {
            throw new NotFoundHttpException('Партнер не найден');
        }
        $partner =  $this->factory->fillPartner($partner, $request);

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(new PartnerResponse($partner));
    }
}
