<?php

namespace App\Domain\Core\LongDrive\Controller\Admin;

use App\Domain\Core\LongDrive\Controller\Admin\Factory\LongDriveStockFactory;
use App\Domain\Core\LongDrive\Controller\Admin\Request\CreateLongDriveStockModelRequest;
use App\Domain\Core\LongDrive\Controller\Admin\Request\ListLongDriveStockRequest;
use App\Domain\Core\LongDrive\Controller\Admin\Response\LongDriveAutoResponse;
use App\Domain\Core\System\Service\Security;
use App\Entity\LongDrive\LongDriveModel;
use App\Entity\LongDrive\LongDrivePartner;
use CarlBundle\Entity\User;
use CarlBundle\Response\Common\BooleanResponse;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LongDriveStockController extends AbstractController
{
    private LongDriveStockFactory $factory;
    private Security $security;

    public function __construct(
        LongDriveStockFactory $factory,
        Security                 $security
    )
    {
        $this->factory = $factory;
        $this->security = $security;
    }

    /**
     * Список моделей лонг-драйва
     *
     * @OA\Get(operationId="/dashboard/admin/long-drive/stock/list")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список моделей",
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
     *              ref=@DocModel(type=LongDriveAutoResponse::class)
     *          )
     *       )
     *     )
     * )
     *
     * @OA\Parameter(
     *     name="partnersId",
     *     in="query",
     *     description="Фильтр по партнерам",
     *     @OA\Schema(type="array", @OA\Items(type="integer"))
     * )
     * @OA\Parameter(
     *     name="brandsId",
     *     in="query",
     *     description="Фильтр по брендам",
     *     @OA\Schema(type="array", @OA\Items(type="integer"))
     * )
     * @OA\Parameter(
     *     name="modelsId",
     *     in="query",
     *     description="Фильтр по моделям",
     *     @OA\Schema(type="array", @OA\Items(type="integer"))
     * )
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
     * @OA\Tag(name="Admin\LongDrive\Stock")
     * @param ListLongDriveStockRequest $request
     *
     * @return JsonResponse
     */
    public function listStock(ListLongDriveStockRequest $request): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user->isLongDrivePartner()) {
            /** @var User $user */
            $request->partnersId = array_map(
                static fn(LongDrivePartner $partner) => $partner->getId(),
                $user->getLongDrivePartnersCollection()->toArray()
            );
        }

        $result = $this
            ->getDoctrine()
            ->getRepository(LongDriveModel::class)
            ->list($request->limit, $request->offset, $request->partnersId, $request->brandsId, $request->modelsId);

        $items = array_map(
            static fn(LongDriveModel $model) => new LongDriveAutoResponse($model),
            $result['items']
        );

        return new JsonResponse(['items' => $items, 'count' => $result['count']]);
    }

    /**
     * Фильтры для списка стоков по лонг-драйвам
     *
     * @OA\Get(operationId="/dashboard/admin/long-drive/stock/filter")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список моделей",
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
     *              ref=@DocModel(type=LongDriveAutoResponse::class)
     *          )
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\LongDrive\Stock")
     *
     * @return JsonResponse
     */
    public function getStockFilters(): JsonResponse
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
            ->getRepository(LongDriveModel::class)
            ->getFilters($partners);

        return new JsonResponse($result);
    }

    /**
     * Получение машины из стока по лонг-драйву
     *
     * @OA\Get(operationId="/dashboard/admin/long-drive/stock/get")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет машину из стока по идентификатору",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=LongDriveAutoResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Admin\LongDrive\Stock")
     * @param int $stockId
     *
     * @return JsonResponse
     */
    public function getStockModel(int $stockId): JsonResponse
    {
        $stockModel = $this->getDoctrine()->getRepository(LongDriveModel::class)->find($stockId);
        if (!$stockModel) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        $user = $this->security->getUser();
        if ($user->isLongDrivePartner()) {
            /** @var User $user */
            $partnersId = array_map(
                static fn(LongDrivePartner $partner) => $partner->getId(),
                $user->getLongDrivePartnersCollection()->toArray()
            );

            if (!in_array($stockModel->getPartner()->getId(), $partnersId, true)) {
                throw new AccessDeniedHttpException();
            }
        }

        return new JsonResponse(new LongDriveAutoResponse($stockModel));
    }

    /**
     * Создание модели в стоке
     *
     * @OA\Post(operationId="/dashboard/admin/long-drive/stock/create")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет созданную модель",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=LongDriveAutoResponse::class)
     *     )
     * )
     *
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@DocModel(type=CreateLongDriveStockModelRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\LongDrive\Stock")
     * @param CreateLongDriveStockModelRequest $request
     *
     * @return JsonResponse
     */
    public function createStockModel(
        CreateLongDriveStockModelRequest $request
    ): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user->isLongDrivePartner()) {
            /** @var User $user */
            $partnersId = array_map(
                static fn(LongDrivePartner $partner) => $partner->getId(),
                $user->getLongDrivePartnersCollection()->toArray()
            );

            if (!in_array($request->partnerId, $partnersId, true)) {
                throw new AccessDeniedHttpException();
            }
        }

        $stockModel = new LongDriveModel();
        $stockModel = $this->factory->fillStockModel($stockModel, $request);

        $this->getDoctrine()->getManager()->persist($stockModel);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(new LongDriveAutoResponse($stockModel));
    }

    /**
     * Обновление модели в стоке
     *
     * @OA\Post(operationId="/dashboard/admin/long-drive/stock/update")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет обновленную модель",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=LongDriveAutoResponse::class)
     *     )
     * )
     *
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@DocModel(type=CreateLongDriveStockModelRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\LongDrive\Stock")
     * @param CreateLongDriveStockModelRequest $request
     * @param int                              $stockId
     *
     * @return JsonResponse
     */
    public function updateStockModel(
        CreateLongDriveStockModelRequest $request,
        int                              $stockId
    ): JsonResponse
    {
        $stockModel = $this->getDoctrine()->getRepository(LongDriveModel::class)->find($stockId);
        if (!$stockModel) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        $user = $this->security->getUser();
        if ($user->isLongDrivePartner()) {
            /** @var User $user */
            $partnersId = array_map(
                static fn(LongDrivePartner $partner) => $partner->getId(),
                $user->getLongDrivePartnersCollection()->toArray()
            );

            if (!in_array($stockModel->getPartner()->getId(), $partnersId, true)) {
                throw new AccessDeniedHttpException();
            }

            if (!in_array($request->partnerId, $partnersId, true)) {
                throw new AccessDeniedHttpException();
            }
        }

        $stockModel = $this->factory->fillStockModel($stockModel, $request);

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(new LongDriveAutoResponse($stockModel));
    }

    /**
     * Удаление модели из стока
     *
     * @OA\Delete(operationId="/dashboard/admin/long-drive/stock/delete")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет результат операции",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=BooleanResponse::class)
     *     )
     * )
     *
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json"
     *     )
     * )
     *
     * @OA\Tag(name="Admin\LongDrive\Stock")
     * @param int $stockId
     *
     * @return BooleanResponse
     */
    public function deleteStockModel(
        int $stockId
    ): BooleanResponse
    {
        $stockModel = $this->getDoctrine()->getRepository(LongDriveModel::class)->find($stockId);
        if (!$stockModel) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        $user = $this->security->getUser();
        if ($user->isLongDrivePartner()) {
            /** @var User $user */
            $partnersId = array_map(
                static fn(LongDrivePartner $partner) => $partner->getId(),
                $user->getLongDrivePartnersCollection()->toArray()
            );

            if (!in_array($stockModel->getPartner()->getId(), $partnersId, true)) {
                throw new AccessDeniedHttpException();
            }
        }

        $this->getDoctrine()->getManager()->remove($stockModel);
        $this->getDoctrine()->getManager()->flush();

        return new BooleanResponse(true);
    }
}
