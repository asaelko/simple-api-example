<?php

namespace App\Domain\Core\LongDrive\Controller\Admin;

use App\Domain\Core\LongDrive\Controller\Admin\Request\GetLongDriveRequestsRequest;
use App\Domain\Core\LongDrive\Controller\Admin\Response\LongDriveRequestResponse;
use App\Domain\Core\System\Service\Security;
use App\Entity\LongDrive\LongDrivePartner;
use App\Entity\LongDrive\LongDriveRequest;
use App\Entity\PartnersMark;
use App\Repository\PartnersMarkRepository;
use CarlBundle\Entity\User;
use CarlBundle\Response\Common\BooleanResponse;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LongDriveRequestsController extends AbstractController
{
    private PartnersMarkRepository $markRepository;
    private Security $security;

    public function __construct(
        PartnersMarkRepository $markRepository,
        Security $security
    )
    {
        $this->markRepository = $markRepository;
        $this->security = $security;
    }

    /**
     * Список поданных заявок на подписку
     *
     * @OA\Get(operationId="/admin/long-drive/request/list")
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
     *              ref=@DocModel(type=LongDriveRequestResponse::class)
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
     * @param GetLongDriveRequestsRequest $request
     *
     * @return JsonResponse
     */
    public function getRequests(GetLongDriveRequestsRequest $request): JsonResponse
    {
        $partners = [];
        $user = $this->security->getUser();
        if ($user->isLongDrivePartner()) {
            /** @var User $user */
            $partners = array_map(
                static fn(LongDrivePartner $partner) => $partner->getId(),
                $user->getLongDrivePartnersCollection()->toArray()
            );
        }

        $result = $this
            ->getDoctrine()
            ->getRepository(LongDriveRequest::class)
            ->list(
                $request->limit,
                $request->offset,
                $request->fromTime,
                $request->toTime,
                $partners
            );

        $marks = $this->markRepository->findBy([
            'partnerRequestClass' => LongDriveRequest::class,
            'partnerRequestId' => array_map(static fn(LongDriveRequest $r) => $r->getId(), $result['items'])
        ]);

        $longDriveMarks = [];
        array_walk($marks, static function (PartnersMark $mark) use (&$longDriveMarks) {
            $longDriveMarks[$mark->getPartnerRequestId()] = $mark->getMark();
        });

        $items = array_map(
            static function (LongDriveRequest $request) use ($longDriveMarks) {
                return new LongDriveRequestResponse(
                    $request,
                    $longDriveMarks[$request->getId()] ?? null
                );
            },
            $result['items']
        );

        return new JsonResponse(['items' => $items, 'count' => $result['count']]);
    }

    /**
     * Удалить заявку
     *
     * @OA\Delete(operationId="/admin/long-drive/request/delete")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет результат запроса",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=BooleanResponse::class)
     *     )
     * )
     *
     * @param int $requestId
     *
     * @OA\Tag(name="Admin\LongDrive")
     *
     * @return BooleanResponse
     */
    public function deleteRequest(int $requestId): BooleanResponse
    {
        $repository = $this->getDoctrine()->getRepository(LongDriveRequest::class);
        $request = $repository->find($requestId);
        if (!$request) {
            throw new NotFoundHttpException('Заявка не найдена');
        }

        $this->getDoctrine()->getManager()->remove($request);
        $this->getDoctrine()->getManager()->flush();

        return new BooleanResponse(true);
    }
}
