<?php

namespace App\Domain\Core\ExperienceCenters\Controller;

use App\Domain\Core\ExperienceCenters\Request\AdminCreateCenterRequest;
use App\Domain\Core\ExperienceCenters\Request\AdminCreateScheduleForCenter;
use App\Domain\Core\ExperienceCenters\Request\AdminDeclineBookRequest;
use App\Domain\Core\ExperienceCenters\Request\AdminGetCenterRequest;
use App\Domain\Core\ExperienceCenters\Request\AdminGetClientRequestsRequest;
use App\Domain\Core\ExperienceCenters\Request\AdminUpdateCenterRequest;
use App\Domain\Core\ExperienceCenters\Response\AdminGetCenterResponse;
use App\Domain\Core\ExperienceCenters\Response\AdminGetClientsRequestsResponse;
use App\Domain\Core\ExperienceCenters\Service\ExperienceCenterBookService;
use App\Domain\Core\ExperienceCenters\Service\ExperienceCenterBrandService;
use App\Entity\ExperienceCenter;
use App\Entity\ExperienceCenterSchedule;
use App\Entity\ExperienceRequest;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\User;
use CarlBundle\Exception\Payment\UnknownPaymentException;
use CarlBundle\Exception\RestException;
use Http\Discovery\Exception\NotFoundException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Operation;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Контроллер для управления экспириенс-центров админом
 */
class AdminController extends AbstractController
{
    /**
     * Создание нового центра
     *
     * Создает новый центр, доступно только для админа
     *
     * @OA\Post(
     *     operationId="brand/experience-center/create",
     *     @OA\RequestBody(
     *          @Model(type=AdminCreateCenterRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт центр",
     *     @OA\JsonContent(
     *          ref=@Model(type=AdminGetCenterResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Admin\ExperienceCenter")
     *
     * @param AdminCreateCenterRequest $request
     * @param ExperienceCenterBrandService $brandService
     * @return JsonResponse
     */
    public function createCenterAction(AdminCreateCenterRequest $request, ExperienceCenterBrandService $brandService): JsonResponse
    {
        $brand = $this->getDoctrine()->getRepository(Brand::class)->find($request->brandId);
        if ($request->brandId && !$brand) {
            throw new NotFoundHttpException("Бренд не найден id {$request->brandId}");
        }

        $center = $brandService->createCenter($brand, $request);

        return new JsonResponse(new AdminGetCenterResponse($center));
    }

    /**
     * Обновление центра
     *
     * Обновляет существующий центр, доступно только для админа
     *
     * @OA\Post(
     *     operationId="brand/experience-center/update",
     *     @OA\RequestBody(
     *          @Model(type=AdminUpdateCenterRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт центр",
     *     @OA\JsonContent(
     *          ref=@Model(type=AdminGetCenterResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Admin\ExperienceCenter")
     *
     * @param AdminUpdateCenterRequest $request
     * @return JsonResponse
     */
    public function updateCenterAction(AdminUpdateCenterRequest $request): JsonResponse
    {
        $center = $this->getDoctrine()->getRepository(ExperienceCenter::class)->find($request->centerId);
        if (!$center) {
            throw new NotFoundHttpException("Центр не найден id {$request->centerId}");
        }

        $center->setName($request->name ?? $center->getName());
        $center->setDescription($request->description ?? $center->getDescription());
        $center->setShortDescription($request->shortDescription ?? $center->getShortDescription());
        $center->setEmailToSendRequest($request->email ?? $center->getEmailToSendRequest());
        $center->setFullOrganizationName($request->organizationName);

        $this->getDoctrine()->getManager()->persist($center);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(new AdminGetCenterResponse($center));
    }

    /**
     * Просмотр центров
     *
     * Вернет список центров, относящихся к брендам
     *
     * @OA\Get(
     *     operationId="brand/experience-center",
     *     @OA\Parameter(
     *          name="brandId",
     *          in="query",
     *          description="Id brand не обязательный параметер если не передавать то вернет все центры всех брендов менеджера",
     *          @OA\Schema(type="integer")
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт список центров",
     *     @OA\JsonContent(
     *          @OA\Property(
     *          property="items",
     *          type="array",
     *          @OA\Items(
     *              ref=@Model(type=AdminGetCenterResponse::class)
     *          )
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\ExperienceCenter")
     *
     * @param AdminGetCenterRequest $request
     * @return JsonResponse
     */
    public function getCentersAction(AdminGetCenterRequest $request): JsonResponse
    {
        if ($request->brandId) {
            $brand = $this->getDoctrine()->getRepository(Brand::class)->find($request->brandId);
            if ($request->brandId && !$brand) {
                throw new NotFoundHttpException("Brand не найден id {$request->brandId}");
            }
            $centers = $this->getDoctrine()->getRepository(ExperienceCenter::class)->findBy(
                [
                    'brand' => $brand
                ]
            );
        } else {
            $manager = $this->getUser();
            assert($manager instanceof User);
            $centers = $this->getDoctrine()->getRepository(ExperienceCenter::class)->findAll();
        }
        $result = [];
        foreach ($centers as $center) {
            $result[] = new AdminGetCenterResponse($center);
        }

        return new JsonResponse(['items' => $result]);
    }

    /**
     * Создать слот
     *
     * Создает новый слот для записи в заданном экспириенс-центре
     *
     * @OA\Post(
     *     operationId="brand/experience-center/slot/create",
     *     @OA\RequestBody(
     *          @Model(type=AdminCreateScheduleForCenter::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт статус",
     *     @OA\JsonContent(
     *          @OA\Property(
     *          property="items",
     *          type="bool",
     *          example=true
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\ExperienceCenter")
     *
     * @param AdminCreateScheduleForCenter $request
     * @param ExperienceCenterBrandService $brandService
     * @return JsonResponse
     */
    public function createScheduleAction(AdminCreateScheduleForCenter $request, ExperienceCenterBrandService $brandService): JsonResponse
    {
        $center = $this->getDoctrine()->getRepository(ExperienceCenter::class)->find($request->centerId);
        if (!$center) {
            throw new NotFoundException("Центр не найден id {$request->centerId}");
        }
        $brand = $center->getBrand();

        $brandService->createSlot($brand, $request);

        return new JsonResponse(['status' => true]);
    }

    /**
     * Просмотр существующих слотов
     *
     * Вернёт слоты и запросы на запись, если они есть
     *
     * @OA\Get(
     *     operationId="brand/experience-center/slot-and-request",
     *     @OA\Parameter(
     *          name="centerId",
     *          in="query",
     *          description="centerId",
     *          @OA\Schema(type="integer")
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт слоты и запросы если есть",
     *     @OA\JsonContent(
     *          @OA\Property (
     *              property="items",
     *              type="array",
     *              @OA\Items(
     *                  ref=@Model(type=AdminGetClientsRequestsResponse::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\ExperienceCenter")
     *
     * @param AdminGetClientRequestsRequest $request
     * @return JsonResponse
     */
    public function getRequestsByBrand(AdminGetClientRequestsRequest $request): JsonResponse
    {
        $center = $this->getDoctrine()->getRepository(ExperienceCenter::class)->find($request->centerId);
        if (!$center) {
            throw new NotFoundException("Центр не найден id {$request->centerId}");
        }

        $requestResult = array_map(
            function (ExperienceCenterSchedule $slot)
            {
                return new AdminGetClientsRequestsResponse(
                    $slot,
                    $slot->getScheduleRequest()->isEmpty()
                );
            }, $center->getScheduleSlots()->toArray()
        );
        return new JsonResponse(['items' => $requestResult]);
    }

    /**
     * Отмена бронирования
     * @Operation(description="Отмена бронирования, доступно только для бренд менеджера")
     *
     * @OA\Post(
     *     operationId="brand/experience-center/request/decline",
     *     @OA\RequestBody(
     *          @Model(type=AdminDeclineBookRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт статус",
     *     @OA\JsonContent(
     *           @OA\Property (
     *           property="status",
     *           type="bool",
     *           example=true
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\ExperienceCenter")
     *
     * @param AdminDeclineBookRequest $request
     * @param ExperienceCenterBookService $bookService
     * @return JsonResponse
     * @throws UnknownPaymentException
     * @throws RestException
     */
    public function declineBook(AdminDeclineBookRequest $request, ExperienceCenterBookService $bookService): JsonResponse
    {
        $bookRequest = $this->getDoctrine()->getRepository(ExperienceRequest::class)->find($request->requestId);
        if (!$bookRequest) {
            throw new NotFoundHttpException('Booking request not found');
        }

        return new JsonResponse(['status' => $bookService->declineRequestByBrand($bookRequest)]);
    }
}
