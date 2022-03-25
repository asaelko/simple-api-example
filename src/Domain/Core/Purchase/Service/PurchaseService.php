<?php

namespace App\Domain\Core\Purchase\Service;

use App\Domain\Notifications\Messages\Client\Purchase\Push\PurchaseAcceptedPushMessage;
use App\Domain\Notifications\Messages\Client\Purchase\Push\PurchaseDeclinedPushMessage;
use App\Entity\Purchase\Purchase;
use AppBundle\User\AbstractAuthorizableUser;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Exception\RestException;
use CarlBundle\Exception\ValueAlreadyUsedException;
use CarlBundle\Service\SlackNotificatorService;
use CarlBundle\ServiceRepository\Model\ModelRepository;
use CarlBundle\ServiceRepository\Purchase\PurchaseRepository;
use DateTime;
use Exception;
use Fxp\Component\Security\Exception\AccessDeniedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use function is_object;

/**
 * Сервис работы с заявками о покупке
 */
class PurchaseService
{
    private TokenStorageInterface $TokenStorage;

    private PurchaseRepository $PurchaseRepository;

    private LoggerInterface $Logger;

    private ModelRepository $ModelRepository;

    private SlackNotificatorService $slackNotificatorService;

    private MessageBusInterface $messageBus;

    public function __construct(
        LoggerInterface $Logger,
        TokenStorageInterface $TokenStorage,
        PurchaseRepository $PurchaseRepository,
        ModelRepository $ModelRepository,
        SlackNotificatorService $slackNotificatorService,
        MessageBusInterface $messageBus
    )
    {
        $this->Logger = $Logger;
        $this->TokenStorage = $TokenStorage;
        $this->PurchaseRepository = $PurchaseRepository;
        $this->ModelRepository = $ModelRepository;
        $this->slackNotificatorService = $slackNotificatorService;
        $this->messageBus = $messageBus;
    }

    /**
     * Получаем текущего пользователя в сервисе
     *
     * @return AbstractAuthorizableUser|null
     */
    private function getUser(): ?AbstractAuthorizableUser
    {
        $token = $this->TokenStorage->getToken();
        if (!$token || !is_object($token->getUser())) {
            return null;
        }
        /** @var AbstractAuthorizableUser $User */
        $User = $token->getUser();

        return $User;
    }

    /**
     * Получаем заявку по её ID
     *
     * @param string $purchaseId
     *
     * @return Purchase
     */
    public function get(string $purchaseId): Purchase
    {
        $Purchase = $this->PurchaseRepository->find($purchaseId);

        if (!$Purchase) {
            throw new NotFoundHttpException('Заявка не найдена');
        }

        $CurrentUser = $this->getUser();

        if (
            !$CurrentUser
            ||
            (
                !$CurrentUser->isAdmin()
                &&
                ($CurrentUser->isClient() && $CurrentUser !== $Purchase->getClient())
            )
        ) {
            throw new AccessDeniedHttpException('Доступ запрещен');
        }

        return $Purchase;
    }

    /**
     * Поулчаем активную заявку от клиента по модели
     *
     * @param int $modelId
     *
     * @return Purchase|null
     */
    public function getActivePurchaseForModel(int $modelId): ?Purchase
    {
        /** @var Client $Client */
        $Client = $this->getUser();

        if (!$Client || !$Client->isClient()) {
            throw new AccessDeniedHttpException('Доступ запрещен');
        }

        /** @var Model $Model */
        $Model = $this->ModelRepository->find($modelId);

        if (!$Model) {
            throw new NotFoundHttpException('Запрошенная модель не найдена на сервере');
        }

        $ActivePurchases = $this->PurchaseRepository->getClientPurchaseRequestsForModel($Client, $Model, false);

        return end($ActivePurchases) ?: null;
    }

    /**
     * Поулчаем активную заявку от клиента
     *
     * @return Purchase|null
     */
    public function getActivePurchase(): ?Purchase
    {
        /** @var Client $Client */
        $Client = $this->getUser();

        if (!$Client || !$Client->isClient()) {
            throw new AccessDeniedHttpException('Доступ запрещен');
        }

        $ActivePurchases = $this->PurchaseRepository->getClientPurchaseRequests($Client, true);

        return end($ActivePurchases) ?: null;
    }

    /**
     * Получаем список заявок на покупку от клиента
     */
    public function getAllPurchaseRequestsForClient(): array
    {
        $Client = $this->getUser();
        if ($Client || !$Client->isClient()) {
            throw new AccessDeniedHttpException('Доступ запрещен');
        }

        /** @var Client $Client */
        return $this->PurchaseRepository->getAllClientPurchaseRequests($Client);
    }

    /**
     * Получаем список всех неподтвержденных заявок на покупку
     *
     * @param int|null $limit
     * @param int|null $offset
     * @param array|null $filter
     *
     * @return array
     */
    public function getPurchasesList(?int $limit = null, ?int $offset = null, ?array $filter = []): array
    {
        return $this->PurchaseRepository->getPurchasesList($limit, $offset, $filter);
    }

    /**
     * Обрабатываем запрос на покупку от клиента
     *
     * @param Purchase $Purchase
     *
     * @return Purchase
     *
     * @throws ValueAlreadyUsedException
     * @throws RestException
     */
    public function processPurchaseRequest(Purchase $Purchase): Purchase
    {
        /** @var Client $Client */
        $Client = $this->getUser();
        if (!$Client || !$Client->isClient()) {
            throw new AccessDeniedException('Доступ запрещен');
        }

        // проверяем, что у клиента еще нет активной отправленной заявки по этому автомобилю
        $ActivePurchases = $this->PurchaseRepository->getClientPurchaseRequestsForModel(
            $Client,
            $Purchase->getModel(),
            true
        );

        if ($ActivePurchases) {
            throw new ValueAlreadyUsedException('По этой модели уже есть активная заявка о покупке');
        }

        try {
            $Purchase->setClient($Client)
                ->setReceiptDecision(Purchase::RECEIPT_NEW)
                ->setRequestedAt(new DateTime());

            $this->PurchaseRepository->persist($Purchase);
            $this->PurchaseRepository->flush();
        } catch (Exception $Ex) {
            $this->Logger->critical($Ex);
            throw new RestException('Не удалось отправить заявку, пожалуйста, попробуйте позднее');
        }

        return $Purchase;
    }

    /**
     * Отменяем существующий запрос на оффер
     *
     * @param string $purchaseId
     * @return Purchase
     *
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
     * @throws RestException
     */
    public function delete(string $purchaseId): Purchase
    {
        $Purchase = $this->get($purchaseId);

        if (!$Purchase) {
            throw new NotFoundHttpException('Заявка не найдена');
        }

        $CurrentUser = $this->getUser();
        if (!$CurrentUser || !$CurrentUser->isAdmin() || ($CurrentUser->isClient() && $CurrentUser !== $Purchase->getClient())) {
            throw new AccessDeniedHttpException('Доступ запрещен');
        }

        try {
            $Purchase->setReceiptDecision(Purchase::RECEIPT_DECLINED)
                ->setDecidedAt(new DateTime());

            if ($CurrentUser->isClient()) {
                $Purchase->setDeclineReason('Отменена клиентом');
            }

            if ($CurrentUser->isAdmin()) {
                $Purchase->setDeclineReason('Удалена администрацией');
            }

            $this->PurchaseRepository->flush();
        } catch (Exception $Ex) {
            $this->Logger->error($Ex);

            throw new RestException('Невозможно удалить заявку, попробуйте позднее');
        }

        return $Purchase;
    }

    /**
     * Обновляем заявку на покупку
     *
     * @param Purchase $Purchase
     * @return Purchase
     */
    public function update(Purchase $Purchase): Purchase
    {
        if ($Purchase->getReceiptDecision() === Purchase::RECEIPT_ACCEPTED) {
            $this->messageBus->dispatch(
                new PurchaseAcceptedPushMessage($Purchase)
            );

            $this->slackNotificatorService->sendNewPurchase($Purchase);
        }

        if ($Purchase->getReceiptDecision() === Purchase::RECEIPT_DECLINED) {
            $this->messageBus->dispatch(
                new PurchaseDeclinedPushMessage($Purchase)
            );
        }

        $this->PurchaseRepository->flush();

        return $Purchase;
    }
}
