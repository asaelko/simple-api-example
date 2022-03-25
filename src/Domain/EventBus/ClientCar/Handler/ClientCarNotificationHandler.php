<?php

namespace App\Domain\EventBus\ClientCar\Handler;

use App\Domain\EventBus\ClientCar\Event\ClientCarApprovedNotificationEvent;
use App\Domain\EventBus\ClientCar\Event\ClientCarDeclinedNotificationEvent;
use App\Domain\Notifications\Messages\Client\ClientCar\Push\ClientCarApprovedPushMessage;
use App\Domain\Notifications\Messages\Client\ClientCar\Push\ClientCarDeclinedPushMessage;
use App\Domain\Notifications\Push\Sender\PushSenderInterface;
use CarlBundle\Entity\ClientCar;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

/**
 * Обработчик событий из шины по клиентской машине
 */
class ClientCarNotificationHandler implements MessageSubscriberInterface
{
    private PushSenderInterface $pushService;

    private EntityManagerInterface $entityManager;

    private LoggerInterface $notificationLogger;

    public function __construct(
        PushSenderInterface $pushService,
        EntityManagerInterface $entityManager,
        LoggerInterface $notificationLogger
    )
    {
        $this->pushService = $pushService;
        $this->entityManager = $entityManager;
        $this->notificationLogger = $notificationLogger;
    }

    /**
     * @inheritDoc
     */
    public static function getHandledMessages(): iterable
    {
        yield ClientCarApprovedNotificationEvent::class => [
            'method' => 'sendClientCarApprovedNotification'
        ];

        yield ClientCarDeclinedNotificationEvent::class => [
            'method' => 'sendClientCarDeclinedNotification'
        ];
    }

    /**
     * Отправляем клиенту пуш о подтверждении его заявки на добавление собственного авто
     *
     * @param ClientCarApprovedNotificationEvent $clientCarEvent
     */
    public function sendClientCarApprovedNotification(ClientCarApprovedNotificationEvent $clientCarEvent): void
    {
        $clientCar = $this->resolveClientCar($clientCarEvent->getClientCarId());

        $this->notificationLogger->info(
            'Отправляем пуш с подтверждением заявки на добавление машины клиенту',
            ['clientCar' => $clientCar]
        );

        $this->pushService->processPush(
            new ClientCarApprovedPushMessage($clientCar)
        );
    }

    /**
     * Отправляем клиенту пуш об отклонении его заявки на добавление собственного авто
     *
     * @param ClientCarDeclinedNotificationEvent $clientCarEvent
     */
    public function sendClientCarDeclinedNotification(ClientCarDeclinedNotificationEvent $clientCarEvent): void
    {
        $clientCar = $this->resolveClientCar($clientCarEvent->getClientCarId());

        $this->notificationLogger->info(
            'Отправляем пуш с отклонением заявки на добавление машины клиенту',
            ['clientCar' => $clientCar]
        );

        $this->pushService->processPush(
            new ClientCarDeclinedPushMessage($clientCar)
        );
    }

    /**
     * Определяем, с какой клиентской машиной мы работаем
     *
     * @param int $clientCarId
     * @return ClientCar
     */
    private function resolveClientCar(int $clientCarId): ClientCar
    {
        $clientCar = $this->entityManager->getRepository(ClientCar::class)->find($clientCarId);

        if (!$clientCar) {
            $this->notificationLogger->error(
                'Не найдена машина клиента для обработки',
                ['clientCar' => $clientCarId]
            );
            throw new UnrecoverableMessageHandlingException('Клиентская машина не найдена');
        }

        return $clientCar;
    }
}
