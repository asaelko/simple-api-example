<?php

namespace App\Domain\EventBus\TestDrive\Handler;

use App\Domain\EventBus\TestDrive\Event\DriveAcceptedClientNotificationEvent;
use App\Domain\EventBus\TestDrive\Event\DriveCancelledClientNotificationEvent;
use App\Domain\EventBus\TestDrive\Event\DriveIsNearClientNotificationEvent;
use App\Domain\EventBus\TestDrive\Event\TestDriveIn24HoursClientNotificationEvent;
use App\Domain\EventBus\TestDrive\Event\TestDriveIn2HoursClientNotificationEvent;
use App\Domain\Notifications\Messages\Client\TestDrive\Push\TestDriveAcceptedPushMessage;
use App\Domain\Notifications\Messages\Client\TestDrive\Push\TestDriveCancelPushMessage;
use App\Domain\Notifications\Messages\Client\TestDrive\Push\TestDriveDriverNearbyPushMessage;
use App\Domain\Notifications\Messages\Client\TestDrive\Push\TestDriveIn24HoursPushMessage;
use App\Domain\Notifications\Messages\Client\TestDrive\Push\TestDriveIn2HoursPushMessage;
use App\Domain\Notifications\Push\PushMessageInterface;
use App\Domain\Notifications\Push\Sender\PushSenderInterface;
use AppBundle\Service\Phone\PhoneService;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use CarlBundle\Exception\CantSendSMSException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Throwable;

/**
 * Обработчик уведомлений по поездкам
 */
class DriveNotificationHandler implements MessageSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    private PushSenderInterface $pushProvider;

    private LoggerInterface $logger;

    /**
     * @var PhoneService
     */
    private PhoneService $phoneService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PushSenderInterface $pushProvider,
        PhoneService $phoneService,
        LoggerInterface $notificationLogger
    )
    {
        $this->entityManager = $entityManager;
        $this->pushProvider = $pushProvider;
        $this->logger = $notificationLogger;
        $this->phoneService = $phoneService;
    }

    /**
     * @inheritDoc
     */
    public static function getHandledMessages(): iterable
    {
        yield TestDriveIn2HoursClientNotificationEvent::class => [
            'method' => 'notifyClientAboutDriveTwoHoursBeforeStart'
        ];

        yield TestDriveIn24HoursClientNotificationEvent::class => [
            'method' => 'notifyClientAboutDriveDayBeforeStart'
        ];

        yield DriveAcceptedClientNotificationEvent::class => [
            'method' => 'notifyClientAboutAcceptedDrive'
        ];

        yield DriveIsNearClientNotificationEvent::class => [
            'method' => 'notifyClientAboutDriveUp'
        ];

        yield DriveCancelledClientNotificationEvent::class => [
            'method' => 'notifyClientAboutCancelledDrive'
        ];
    }

    /**
     * @param TestDriveIn2HoursClientNotificationEvent $event
     */
    public function notifyClientAboutDriveTwoHoursBeforeStart(TestDriveIn2HoursClientNotificationEvent $event): void
    {
        $drive = $this->resolveDrive($event->getDriveId());

        if ($drive->getState() !== Drive::STATE_NEW || !$drive->getStart()) {
            $this->logger->info(
                'Невозможно отправить пуш за два часа до поездки, так как поездка в некорректном состоянии',
                ['drive' => $drive->getId(), 'state' => $drive->getState(), 'client' => $drive->getClient()->getId()]
            );
            return;
        }

        $driveStart = $drive->getStart()->getTimestamp();
        $timeBeforeStart = $driveStart - time();
        if ($timeBeforeStart < (2 * 60 * 60 - 10 * 60) || $timeBeforeStart > (2 * 60 * 60 + 10 * 60)) {
            $this->logger->info(
                'Не отправляем пуш о старте поездки за два часа, так как время отправки не актуально',
                ['drive' => $drive->getId(), 'timeDiff' => $timeBeforeStart, 'client' => $drive->getClient()->getId()]
            );
            return;
        }

        $this->logger->info(
            'Отправляем уведомление о старте поездки за два часа',
            ['drive' => $drive->getId(), 'timeDiff' => $timeBeforeStart, 'client' => $drive->getClient()->getId()]
        );
        $pushData = new TestDriveIn2HoursPushMessage($drive);

        $this->sendNotification($drive->getClient(), $pushData);
    }

    /**
     * @param TestDriveIn24HoursClientNotificationEvent $event
     */
    public function notifyClientAboutDriveDayBeforeStart(TestDriveIn24HoursClientNotificationEvent $event): void
    {
        $drive = $this->resolveDrive($event->getDriveId());

        if ($drive->getState() !== Drive::STATE_NEW || !$drive->getStart()) {
            $this->logger->info(
                'Невозможно отправить пуш за сутки до поездки, так как поездка в некорректном состоянии',
                ['drive' => $drive->getId(), 'state' => $drive->getState(), 'client' => $drive->getClient()->getId()]
            );
            return;
        }

        $driveStart = $drive->getStart()->getTimestamp();
        $timeBeforeStart = $driveStart - (new DateTime())->getTimestamp();
        if ($timeBeforeStart < (24 * 60 * 60 - 10 * 60) || $timeBeforeStart > (24 * 60 * 60 + 10 * 60)) {
            $this->logger->info(
                'Не отправляем пуш о старте поездки за сутки, так как время отправки не актуально',
                ['drive' => $drive->getId(), 'timeDiff' => $timeBeforeStart, 'client' => $drive->getClient()->getId()]
            );
            return;
        }

        $pushData = new TestDriveIn24HoursPushMessage($drive);

        $this->logger->info(
            'Отправляем уведомление о старте поездки за сутки',
            ['drive' => $drive->getId(), 'client' => $drive->getClient()->getId()]
        );

        $this->sendNotification($drive->getClient(), $pushData);
    }

    /**
     * Уведомляем клиента о том, что поездка принята
     *
     * @param DriveAcceptedClientNotificationEvent $event
     */
    public function notifyClientAboutAcceptedDrive(DriveAcceptedClientNotificationEvent $event): void
    {
        $drive = $this->resolveDrive($event->getDriveId());

        $pushData = new TestDriveAcceptedPushMessage($drive);

        $this->logger->info(
            'Отправляем уведомление о принятии поездки',
            ['drive' => $drive->getId(), 'client' => $drive->getClient()->getId()]
        );

        $this->sendNotification($drive->getClient(), $pushData);
    }

    /**
     * Уведомляем клиента о том, что водитель подъезжает
     *
     * @param DriveIsNearClientNotificationEvent $event
     */
    public function notifyClientAboutDriveUp(DriveIsNearClientNotificationEvent $event): void
    {
        $drive = $this->resolveDrive($event->getDriveId());

        $this->logger->info(
            'Отправляем пуш о том, что водитель подъезжает',
            ['drive' => $drive->getId(), 'client' => $drive->getClient()->getId()]
        );

        $pushData = new TestDriveDriverNearbyPushMessage($drive);

        $this->sendNotification($drive->getClient(), $pushData);
    }

    /**
     * Уведомляем клиента о том, что подъездка отменена
     * @param DriveCancelledClientNotificationEvent $event
     */
    public function notifyClientAboutCancelledDrive(DriveCancelledClientNotificationEvent $event): void
    {
        $drive = $this->resolveDrive($event->getDriveId());

        $this->logger->info(
            'Отправляем пуш об отмененной поездке',
            ['drive' => $drive->getId(), 'client' => $drive->getClient()->getId()]
        );

        $pushData = new TestDriveCancelPushMessage($drive);

        $this->sendNotification($drive->getClient(), $pushData);
    }

    /**
     * @param Client $client
     * @param PushMessageInterface $pushNotification
     */
    private function sendNotification(Client $client, PushMessageInterface $pushNotification): void
    {
        if (!$client->getPushToken() && $client->getPhone()) {
            try {
                $this->phoneService->sendSms($client->getPhone(), $pushNotification->getText(), $client->getAppTag(), $pushNotification->getContext());
            } catch (CantSendSMSException $e) {
                $this->logger->info($e, [
                    'title'  => $pushNotification->getTitle(),
                    'body'   => $pushNotification->getText(),
                    'client' => $client->getId()
                ]);
            }
            return;
        }

        try {
            $this->pushProvider->processPush($pushNotification);
        } catch (Throwable $e) {
            $this->logger->info($e, [
                'title'  => $pushNotification->getTitle(),
                'body'   => $pushNotification->getText(),
                'client' => $client->getId()
            ]);
        }
    }

    /**
     * @param int $driveId
     * @return Drive
     */
    private function resolveDrive(int $driveId): Drive
    {
        $drive = $this->entityManager->getRepository(Drive::class)->find($driveId);

        if (!$drive) {
            throw new UnrecoverableMessageHandlingException('Поездка для обработки не найдена');
        }

        return $drive;
    }
}
