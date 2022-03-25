<?php

namespace App\Domain\Core\Subscription\Controller\Client;

use App\Domain\Core\Client\Service\ClientAuthService;
use App\Domain\Core\Subscription\Controller\Client\Request\AnonymousSubscriptionRequest;
use App\Domain\Core\Subscription\Controller\Client\Response\SubscriptionModelListItemResponse;
use App\Domain\EventBus\Subscription\SubscriptionQueryCreatedEvent;
use App\Domain\EventBus\Subscription\SubscriptionRequestCreatedEvent;
use App\Entity\Subscription\SubscriptionQuery;
use App\Entity\SubscriptionModel;
use App\Entity\SubscriptionRequest;
use AppBundle\Service\AppConfig;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Exception\ClientIsBanLoginException;
use CarlBundle\Exception\RestException;
use CarlBundle\Service\ClientService;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

class SubscriptionClientController extends AbstractController
{
    private MessageBusInterface $messageBus;
    private ClientService $clientService;
    private ClientAuthService $authService;
    private AppConfig $appConfig;

    public function __construct(
        MessageBusInterface $messageBus,
        ClientService $clientService,
        ClientAuthService $authService,
        AppConfig $appConfig
    )
    {
        $this->messageBus = $messageBus;
        $this->clientService = $clientService;
        $this->authService = $authService;
        $this->appConfig = $appConfig;
    }

    /**
     * Доступные варианты подписки для модели
     *
     * @OA\Get(operationId="/client/subscription/list-by-model")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список предложений партнеров по модели",
     *     @OA\JsonContent(
     *        @OA\Property(
     *        property="items",
     *        type="array",
     *          @OA\Items(
     *              ref=@DocModel(type=SubscriptionModelListItemResponse::class)
     *          )
     *       )
     *     )
     * )
     *
     * @OA\Tag(name="Client\Subscription")
     * @param int $modelId
     *
     * @return JsonResponse
     */
    public function getSubscriptionsList(int $modelId): JsonResponse
    {
        $partners = $this->appConfig->getCurrentConfig()['subscriptions'];

        $model = $this->getDoctrine()->getRepository(Model::class)->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException("Модель #{$modelId} не найдена");
        }

        $findBy = [
            'model' => $model,
            'deletedAt' => null
        ];
        if ($partners) {
            $findBy['partner'] = $partners;
        }
        $cars = $this->getDoctrine()->getRepository(SubscriptionModel::class)->findBy($findBy);

        $requested = [];
        if ($this->getUser() && $this->getUser() instanceof Client) {
            $requested = $this->getDoctrine()->getRepository(SubscriptionRequest::class)->findBy(
                ['client' => $this->getUser()]
            );
            $requested = array_map(static fn(SubscriptionRequest $request) => $request->getModel()->getId() , $requested);
        }

        $result = array_map(
            static fn(SubscriptionModel $model) => new SubscriptionModelListItemResponse($model, $requested),
            $cars
        );

        return new JsonResponse(['items' => $result]);
    }

    /**
     * Заявка на подписку
     *
     * @OA\Post(operationId="/client/subscription/request/create")
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
     * @OA\Tag(name="Client\Subscription")
     * @param int $subscriptionModelId
     *
     * @return JsonResponse
     * @throws RestException
     */
    public function createSubscriptionRequest(int $subscriptionModelId): JsonResponse
    {
        $user = $this->getUser();
        assert($user instanceof Client);
        $model = $this->getDoctrine()->getRepository(SubscriptionModel::class)->find($subscriptionModelId);

        if (!$model) {
            throw new NotFoundHttpException("Подписка #{$subscriptionModelId} не найдена");
        }

        $oldRequest = $this->getDoctrine()->getRepository(SubscriptionRequest::class)->findBy([
            'model' => $model->getId(),
            'client' => $user
        ]);

        if (!empty($oldRequest)) {
            throw new RestException('Уже есть заявка на данную модель');
        }

        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->setModel($model);
        $subscriptionRequest->setPartner($model->getPartner());
        $subscriptionRequest->setClient($user);
        $subscriptionRequest->setPrice($model->getPrice());
        $subscriptionRequest->setTerm(12);
        $subscriptionRequest->setContractSum(12 * $model->getPrice());

        $this->getDoctrine()->getManager()->persist($subscriptionRequest);
        $this->getDoctrine()->getManager()->flush();

        $this->messageBus->dispatch(new SubscriptionRequestCreatedEvent($subscriptionRequest));

        return new JsonResponse(['status' => true]);
    }

    /**
     * Анонимная заявка на подписку
     *
     * @OA\Post(
     *     operationId="/web/client/subscription/request/create",
     *     @OA\RequestBody(
     *          @DocModel(type=AnonymousSubscriptionRequest::class)
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
     * @OA\Tag(name="Web\Subscription")
     *
     * @param AnonymousSubscriptionRequest $request
     * @param int                          $subscriptionModelId
     *
     * @return JsonResponse
     * @throws RestException
     * @throws ClientIsBanLoginException
     */
    public function createAnonSubscriptionRequest(AnonymousSubscriptionRequest $request, int $subscriptionModelId): JsonResponse
    {
        $client = $this->authService->tryAuthBy(['phone' => $request->phone]);
        $client ??= $this->authService->tryAuthBy(['email' => $request->email]);
        if (!$client) {
            $client = $this->clientService->createClientByPhone($request->phone);
            $client->setFirstName($request->firstName)
                ->setSecondName($request->secondName);
            $client->setAppTag($this->appConfig->getAppId());
        }

        $subscriptionModel = $this->getDoctrine()->getRepository(SubscriptionModel::class)->find($subscriptionModelId);

        if (!$subscriptionModel) {
            throw new NotFoundHttpException("Подписка для модели #{$subscriptionModelId} не доступна");
        }

        $oldRequest = $this->getDoctrine()->getRepository(SubscriptionRequest::class)->findBy([
            'model' => $subscriptionModel,
            'client' => $client
        ]);

        if (!empty($oldRequest)) {
            throw new RestException('Уже есть заявка на данную модель');
        }

        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->setModel($subscriptionModel);
        $subscriptionRequest->setPartner($subscriptionModel->getPartner());
        $subscriptionRequest->setClient($client);
        $subscriptionRequest->setPrice($subscriptionModel->getPrice());
        $subscriptionRequest->setTerm(12);
        $subscriptionRequest->setContractSum(12 * $subscriptionModel->getPrice());

        $this->getDoctrine()->getManager()->persist($subscriptionRequest);
        $this->getDoctrine()->getManager()->flush();

        $this->messageBus->dispatch(new SubscriptionRequestCreatedEvent($subscriptionRequest));

        return new JsonResponse(['status' => true]);
    }

    /**
     * Пожелание подписки на модель
     *
     * @OA\Post(
     *     operationId="/client/subscription/query/create"
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
     * @OA\Tag(name="Client\Subscription")
     *
     * @param int                          $modelId
     *
     * @return JsonResponse
     * @throws RestException
     * @throws ClientIsBanLoginException
     */
    public function createSubscriptionQuery(int $modelId): JsonResponse
    {
        $user = $this->getUser();
        assert($user instanceof Client);

        $model = $this->getDoctrine()->getRepository(Model::class)->find($modelId);
        if (!$model) {
            throw new NotFoundHttpException("Модель #{$modelId} не найдена");
        }

        $oldRequest = $this->getDoctrine()->getRepository(SubscriptionQuery::class)->findBy([
            'model' => $model,
            'client' => $user
        ]);

        if (!empty($oldRequest)) {
            throw new RestException('Уже есть заявка на данную модель');
        }

        $subscriptionQuery = new SubscriptionQuery();
        $subscriptionQuery->setModel($model);
        $subscriptionQuery->setClient($user);

        $this->getDoctrine()->getManager()->persist($subscriptionQuery);
        $this->getDoctrine()->getManager()->flush();

        $this->messageBus->dispatch(new SubscriptionQueryCreatedEvent($subscriptionQuery));

        return new JsonResponse(['status' => true]);
    }

    /**
     * Пожелание подписки на модель
     *
     * @OA\Post(
     *     operationId="/web/client/subscription/query/create",
     *     @OA\RequestBody(
     *          @DocModel(type=AnonymousSubscriptionRequest::class)
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
     * @OA\Tag(name="Web\Subscription")
     *
     * @param AnonymousSubscriptionRequest $request
     * @param int                          $modelId
     *
     * @return JsonResponse
     * @throws RestException
     * @throws ClientIsBanLoginException
     */
    public function createAnonSubscriptionQuery(AnonymousSubscriptionRequest $request, int $modelId): JsonResponse
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

        $oldRequest = $this->getDoctrine()->getRepository(SubscriptionQuery::class)->findBy([
            'model' => $model,
            'client' => $client
        ]);

        if (!empty($oldRequest)) {
            throw new RestException('Уже есть заявка на данную модель');
        }

        $subscriptionQuery = new SubscriptionQuery();
        $subscriptionQuery->setModel($model);
        $subscriptionQuery->setClient($client);

        $this->getDoctrine()->getManager()->persist($subscriptionQuery);
        $this->getDoctrine()->getManager()->flush();

        $this->messageBus->dispatch(new SubscriptionQueryCreatedEvent($subscriptionQuery));

        return new JsonResponse(['status' => true]);
    }
}