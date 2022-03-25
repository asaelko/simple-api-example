<?php

namespace App\Domain\Core\LongDrive\Controller\Client;

use App\Domain\Core\Client\Service\ClientAuthService;
use App\Domain\Core\LongDrive\Controller\Client\Request\AnonymousLongDriveRequest;
use App\Domain\Core\LongDrive\Controller\Client\Response\LongDriveModelListItemResponse;
use App\Entity\LongDrive\LongDriveModel;
use App\Entity\LongDrive\LongDriveQuery;
use App\Entity\LongDrive\LongDriveRequest;
use AppBundle\Service\AppConfig;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Exception\ClientIsBanLoginException;
use CarlBundle\Exception\RestException;
use CarlBundle\Service\ClientService;
use CarlBundle\Service\SlackNotificatorService;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LongDriveClientController extends AbstractController
{
    private ClientService $clientService;
    private ClientAuthService $authService;
    private AppConfig $appConfig;
    private SlackNotificatorService $slackNotificatorService;

    public function __construct(
        ClientService           $clientService,
        ClientAuthService       $authService,
        AppConfig               $appConfig,
        SlackNotificatorService $slackNotificatorService
    )
    {
        $this->clientService = $clientService;
        $this->authService = $authService;
        $this->appConfig = $appConfig;
        $this->slackNotificatorService = $slackNotificatorService;
    }

    /**
     * Доступные варианты лонг-драйвов для модели
     *
     * @OA\Get(operationId="/client/long-drive/list-by-model")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список предложений партнеров по модели",
     *     @OA\JsonContent(
     *        @OA\Property(
     *        property="items",
     *        type="array",
     *          @OA\Items(
     *              ref=@DocModel(type=LongDriveModelListItemResponse::class)
     *          )
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Client\LongDrive")
     * @param int $modelId
     *
     * @return JsonResponse
     */
    public function getLongDriveList(int $modelId): JsonResponse
    {
        $model = $this->getDoctrine()->getRepository(Model::class)->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException("Модель #{$modelId} не найдена");
        }

        $cars = $this->getDoctrine()->getRepository(LongDriveModel::class)->findBy(
            [
                'model'     => $model,
                'deletedAt' => null,
            ]
        );

        $requested = [];
        if ($this->getUser() && $this->getUser() instanceof Client) {
            $requested = $this->getDoctrine()->getRepository(LongDriveRequest::class)->findBy(
                ['client' => $this->getUser()]
            );
            $requested = array_map(static fn(LongDriveRequest $request) => $request->getModel()->getId(), $requested);
        }

        $result = array_map(
            static fn(LongDriveModel $model) => new LongDriveModelListItemResponse($model, $requested),
            $cars
        );

        return new JsonResponse(['items' => $result]);
    }

    /**
     * Заявка на лонг-драйв
     *
     * @OA\Post(operationId="/client/long-drive/request/create")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет статус",
     *     @OA\JsonContent(
     *        @OA\Property(
     *            property="status",
     *            type="boolean",
     *            example=true
     *        )
     *     )
     * )
     *
     * @OA\Tag(name="Client\LongDrive")
     * @param int $longDriveModelId
     *
     * @return JsonResponse
     * @throws RestException
     */
    public function createLongDriveRequest(Request\LongDriveRequest $request, int $longDriveModelId): JsonResponse
    {
        $user = $this->getUser();
        assert($user instanceof Client);
        $model = $this->getDoctrine()->getRepository(LongDriveModel::class)->find($longDriveModelId);

        if (!$model) {
            throw new NotFoundHttpException("Подписка #{$longDriveModelId} не найдена");
        }

        $oldRequest = $this->getDoctrine()->getRepository(LongDriveRequest::class)->findBy([
            'model'  => $model->getId(),
            'client' => $user,
        ]);

        if (!empty($oldRequest)) {
            throw new RestException('Уже есть заявка на данную модель');
        }

        $longDriveRequest = new LongDriveRequest();
        $longDriveRequest->setModel($model)
            ->setPartner($model->getPartner())
            ->setClient($user)
            ->setPeriod($request->period);

        if ($request->startAt) {
            $longDriveRequest->setStartAt((new \DateTimeImmutable())::createFromFormat('U', $request->startAt));
        }

        $this->getDoctrine()->getManager()->persist($longDriveRequest);
        $this->getDoctrine()->getManager()->flush();

        $this->slackNotificatorService->sendNewLongDriveRequest($longDriveRequest);

        return new JsonResponse(['status' => true]);
    }

    /**
     * Заявка на лонг-драйв
     *
     * @OA\Post(
     *     operationId="/web/client/long-drive/request/create",
     *     @OA\RequestBody(
     *          @DocModel(type=AnonymousLongDriveRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет статус",
     *     @OA\JsonContent(
     *        @OA\Property(
     *            property="status",
     *            type="boolean",
     *            example=true
     *        )
     *     )
     * )
     *
     * @OA\Tag(name="Web\LongDrive")
     * @param AnonymousLongDriveRequest $request
     * @param int                       $longDriveModelId
     *
     * @return JsonResponse
     * @throws RestException
     * @throws ClientIsBanLoginException
     */
    public function createAnonLongDriveRequest(AnonymousLongDriveRequest $request, int $longDriveModelId): JsonResponse
    {
        $client = $this->authService->tryAuthBy(['phone' => $request->phone]);
        $client ??= $this->authService->tryAuthBy(['email' => $request->email]);
        if (!$client) {
            $client = $this->clientService->createClientByPhone($request->phone);
            $client->setFirstName($request->firstName)
                ->setSecondName($request->secondName);
            $client->setAppTag($this->appConfig->getAppId());
        }

        $longDriveModel = $this->getDoctrine()->getRepository(LongDriveModel::class)->find($longDriveModelId);

        if (!$longDriveModel) {
            throw new NotFoundHttpException("Лонг-драйв для модели #{$longDriveModelId} не доступен");
        }

        $oldRequest = $this->getDoctrine()->getRepository(LongDriveRequest::class)->findBy([
            'model'  => $longDriveModel,
            'client' => $client,
        ]);

        if (!empty($oldRequest)) {
            throw new RestException('Уже есть заявка на данную модель');
        }

        $longDriveRequest = new LongDriveRequest();
        $longDriveRequest->setModel($longDriveModel)
            ->setPartner($longDriveModel->getPartner())
            ->setClient($client)
            ->setPeriod($request->period);

        if ($request->startAt) {
            $longDriveRequest->setStartAt((new \DateTimeImmutable())::createFromFormat('U', $request->startAt));
        }

        $this->getDoctrine()->getManager()->persist($longDriveRequest);
        $this->getDoctrine()->getManager()->flush();

        $this->slackNotificatorService->sendNewLongDriveRequest($longDriveRequest);

        return new JsonResponse(['status' => true]);
    }

    /**
     * Пожелание лонг-драйва
     *
     * @OA\Post(
     *     operationId="/web/client/long-drive/query/create",
     *     @OA\RequestBody(
     *          @DocModel(type=AnonymousLongDriveRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет статус",
     *     @OA\JsonContent(
     *        @OA\Property(
     *            property="status",
     *            type="boolean",
     *            example=true
     *        )
     *     )
     * )
     *
     * @OA\Tag(name="Web\LongDrive")
     * @param AnonymousLongDriveRequest $request
     * @param int                       $modelId
     *
     * @return JsonResponse
     * @throws RestException
     * @throws ClientIsBanLoginException
     */
    public function createAnonLongDriveQuery(AnonymousLongDriveRequest $request, int $modelId): JsonResponse
    {
        $client = $this->authService->tryAuthBy(['phone' => $request->phone]);
        $client ??= $this->authService->tryAuthBy(['email' => $request->email]);
        if (!$client) {
            $client = $this->clientService->createClientByPhone($request->phone);
            $client->setFirstName($request->firstName)
                ->setSecondName($request->secondName);
            $client->setAppTag($this->appConfig->getAppId());
        }

        $model = $this->getDoctrine()->getRepository(Model::class)->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException("Модель #{$modelId} не найдена");
        }

        $oldRequest = $this->getDoctrine()->getRepository(LongDriveQuery::class)->findBy([
            'model'  => $model,
            'client' => $client,
        ]);

        if (!empty($oldRequest)) {
            throw new RestException('Уже есть заявка на данную модель');
        }

        $longDriveQuery = new LongDriveQuery();
        $longDriveQuery->setModel($model);
        $longDriveQuery->setClient($client);

        $this->getDoctrine()->getManager()->persist($longDriveQuery);
        $this->getDoctrine()->getManager()->flush();

        $this->slackNotificatorService->sendNewLongDriveQuery($longDriveQuery);

        return new JsonResponse(['status' => true]);
    }
}