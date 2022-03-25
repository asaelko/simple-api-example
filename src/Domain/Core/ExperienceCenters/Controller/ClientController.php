<?php


namespace App\Domain\Core\ExperienceCenters\Controller;


use App\Domain\Core\ExperienceCenters\Request\ClientBookRequest;
use App\Domain\Core\ExperienceCenters\Request\ClientGetSlotsForBooking;
use App\Domain\Core\ExperienceCenters\Response\AdminGetCenterResponse;
use App\Domain\Core\ExperienceCenters\Response\ClientGetSlotsResponse;
use App\Domain\Core\ExperienceCenters\Response\ClientRequest;
use App\Domain\Core\ExperienceCenters\Service\ExperienceCenterBookService;
use App\Entity\ExperienceCenter;
use App\Entity\ExperienceCenterSchedule;
use App\Entity\ExperienceRequest;
use CarlBundle\Entity\Client;
use CarlBundle\Exception\Payment\UnknownPaymentException;
use CarlBundle\Exception\RestException;
use Http\Discovery\Exception\NotFoundException;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClientController extends AbstractController
{
    /**
     * Просмотр центров
     *
     * Вернёт доступные клиенту центры
     *
     * @OA\Get(
     *     operationId="/client/experience-center",
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт слоты если есть",
     *     @OA\JsonContent(
     *          @OA\Property (
     *              property="items",
     *              type="array",
     *              @OA\Items(
     *                  ref=@Model(type=AdminGetCenterResponse::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Client\ExperienceCenter")
     *
     * @return JsonResponse
     */
    public function getCenters(): JsonResponse
    {
        $centers = $this->getDoctrine()->getRepository(ExperienceCenter::class)->findAll();
        $result = array_map(
            static fn(ExperienceCenter $center) => new AdminGetCenterResponse($center),
            $centers
        );

        return new JsonResponse(['items' => $result]);
    }

    /**
     * Просмотр запросов клиента
     *
     * Вернёт запросы текущего пользователя на запись в экспириенс-центры
     *
     * @OA\Get(
     *     operationId="client/experience-center/client-requests",
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт запросы если есть",
     *     @OA\JsonContent(
     *          @OA\Property (
     *              property="items",
     *              type="array",
     *              @OA\Items(
     *                  ref=@Model(type=ClientRequest::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Client\ExperienceCenter")
     *
     * @return JsonResponse
     * @throws RestException
     */
    public function getClientRequests(): JsonResponse
    {
        if (!($this->getUser() instanceof Client)) {
            throw new RestException('Entity is not a Client');
        }

        $requests = $this->getDoctrine()->getRepository(ExperienceRequest::class)->findBy(
            ['client' => $this->getUser()]
        );
        return new JsonResponse(['items' => array_map(function (ExperienceRequest $request){return new ClientRequest($request);}, $requests)]);
    }

    /**
     * Просмотр свободных слотов центра
     *
     * Отдает свободные слоты расписания для выбранного экспириенс-центра
     *
     * @OA\Get(
     *     operationId="client/experience-center/slots",
     *     @OA\Parameter(
     *          name="centerId",
     *          in="query",
     *          required=true,
     *          description="centerId",
     *          @OA\Schema(type="integer")
     *      )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт запросы если есть",
     *     @OA\JsonContent(
     *          ref=@Model(type=ClientGetSlotsResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Client\ExperienceCenter")
     *
     * @param ClientGetSlotsForBooking $request
     * @return JsonResponse
     */
    public function getSlotsForBook(ClientGetSlotsForBooking $request): JsonResponse
    {
        $center = $this->getDoctrine()->getRepository(ExperienceCenter::class)->find($request->centerId);
        if (!$center) {
            throw new NotFoundException("Center with id {$request->centerId} was not found");
        }
        return new JsonResponse(new ClientGetSlotsResponse($center->getScheduleSlots()->toArray()));
    }

    /**
     * Забронировать слот
     *
     * Отправляет запрос на запись в экспириенс-центр
     *
     * @OA\Post(
     *     operationId="client/experience-center/book",
     *     @OA\RequestBody(
     *          @Model(type=ClientBookRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт стутус",
     *     @OA\JsonContent(
     *           @OA\Property (
     *           property="status",
     *           type="bool",
     *           example=true
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Client\ExperienceCenter")
     *
     * @param ClientBookRequest $request
     * @param ExperienceCenterBookService $bookService
     * @return JsonResponse
     * @throws RestException
     * @throws UnknownPaymentException
     */
    public function bookSlotFromMobileApp(ClientBookRequest $request, ExperienceCenterBookService $bookService): JsonResponse
    {
        $client = $this->getUser();

        if (!($client instanceof Client)) {
            throw new NotFoundHttpException('Client not found');
        }
        $slot = $this->getDoctrine()->getRepository(ExperienceCenterSchedule::class)->find($request->slotId);

        if (!$slot || $slot->getIsBooked()) {
            throw new RestException('Error slot or slot is booked', 430);
        }
        return new JsonResponse(['status' => $bookService->bookSlot($request, $client)]);
    }
}
