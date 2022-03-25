<?php

namespace App\Domain\Core\Partners\Controller;

use App\Domain\Core\Partners\Helper\PartnersMarkHelper;
use App\Domain\Core\Partners\Request\ListPartnersMarkRequest;
use App\Domain\Core\Partners\Request\UpdatePartnersMarkRequest;
use App\Domain\Core\Partners\Response\ListPartnersMarkResponse;
use App\Domain\Core\Partners\Response\PartnersMarkResponse;
use App\Domain\Notifications\Messages\PartnersMark\Message\SendSlackNotificationByPartnersMarkMessage;
use App\Entity\PartnersMark;
use CarlBundle\Entity\Client;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;

class PartnersMarkController extends AbstractController
{
    private PartnersMarkHelper $helper;
    private MessageBusInterface $messageBus;

    public function __construct(
        PartnersMarkHelper $helper,
        MessageBusInterface $messageBus
    )
    {
        $this->helper = $helper;
        $this->messageBus = $messageBus;
    }

    /**
     * @OA\Post(
     *     operationId="client/partners-mark",
     *     @OA\RequestBody(
     *          @Model(type=UpdatePartnersMarkRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет информацию по обновленой оценке",
     *     @OA\JsonContent(
     *           ref=@Model(type=PartnersMarkResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Client\PartnersMark")
     *
     * @param UpdatePartnersMarkRequest $request
     * @return JsonResponse
     */
    public function updatePartnersMarkByClient(
        UpdatePartnersMarkRequest $request
    ): JsonResponse
    {
        $client = $this->getUser();
        assert($client instanceof Client);

        $partnerMark = $this->getDoctrine()->getRepository(PartnersMark::class)->find($request->partnerMarkId);
        assert($partnerMark instanceof PartnersMark);

        $partnerMark->setMark($request->mark);
        $partnerMark->setComment($request->comment);
        $partnerMark->setDateUpdate(new \DateTime());

        $this->getDoctrine()->getManager()->flush($partnerMark);

        $this->messageBus->dispatch(new SendSlackNotificationByPartnersMarkMessage($partnerMark));

        return new JsonResponse(new PartnersMarkResponse($partnerMark, $this->helper->getPartnersName($partnerMark)));
    }

    /**
     * @OA\Get(
     *     operationId="/admin/partners-mark"
     * )
     * @OA\Parameter(
     *  name="limit",
     *  in="query",
     *  required=true,
     *  description="limit",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *  name="offset",
     *  required=true,
     *  in="query",
     *  description="offset",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *  name="partnersId",
     *  in="query",
     *  description="массив Id партнеров",
     *  @OA\Schema(type="array", @OA\Items(type="integeer", example="1"))
     * )
     * @OA\Parameter(
     *  name="types",
     *  required=false,
     *  in="query",
     *  description="Возможные на данный момент значения Кредит,Лизинг,Обратный звонок,КП,Бронировние",
     *  @OA\Schema(type="array", @OA\Items(type="string", example="Кредит"))
     * )
     * @OA\Parameter(
     *  name="marks",
     *  required=false,
     *  in="query",
     *  description="Массив оценок",
     *  @OA\Schema(type="array", @OA\Items(type="integer", example="1"))
     * )
     * @OA\Parameter(
     *  name="clientsId",
     *  required=false,
     *  in="query",
     *  description="Массив идентификаторов клиентов",
     *  @OA\Schema(type="array", @OA\Items(type="integer", example="1"))
     * )
     * @OA\Parameter(
     *  name="fromDateCreate",
     *  required=false,
     *  in="query",
     *  description="TimeStamp создниая записи",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *  name="toDateCreate",
     *  required=false,
     *  in="query",
     *  description="TimeStamp создниая записи",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *  name="fromDateUpdate",
     *  required=false,
     *  in="query",
     *  description="TimeStamp обновления записи",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *  name="toDateUpdate",
     *  required=false,
     *  in="query",
     *  description="TimeStamp обновления записи",
     *  @OA\Schema(type="integer")
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список оценок",
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
     *              ref=@Model(type=ListPartnersMarkResponse::class)
     *          )
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\PartnersMark")
     * @param ListPartnersMarkRequest $request
     * @param PartnersMarkHelper $helper
     * @return JsonResponse
     */
    public function listAction(ListPartnersMarkRequest $request, PartnersMarkHelper $helper): JsonResponse
    {
        $repository = $this->getDoctrine()->getRepository(PartnersMark::class);
        $records = $repository->listRecords($request);
        $result = array_map(
            fn(PartnersMark $mark) => new ListPartnersMarkResponse($mark, $this->helper->getPartnersName($mark)),
            $records['items']
        );

        return new JsonResponse(['items' => $result, 'count' => $records['count']]);
    }
}