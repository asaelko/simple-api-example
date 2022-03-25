<?php

namespace App\Domain\Infrastructure\TelegramBot\Handler;

use App\Domain\Infrastructure\TelegramBot\TelegramBot;
use App\Domain\Marketing\Event\UpdateDriveEvent;
use CarlBundle\Entity\Drive;
use CarlBundle\Repository\DriveRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class DriveUpdateHandler implements MessageSubscriberInterface
{
    private DriveRepository $driveRepository;
    private TelegramBot $telegramBot;
    private LoggerInterface $logger;

    public function __construct(
        DriveRepository $driveRepository,
        TelegramBot $telegramBot,
        LoggerInterface $telegramBotLogger
    )
    {
        $this->driveRepository = $driveRepository;
        $this->telegramBot = $telegramBot;
        $this->logger = $telegramBotLogger;
    }

    /**
     * @inheritDoc
     */
    public static function getHandledMessages(): iterable
    {
        yield UpdateDriveEvent::class => [
            'method' => 'processDriveUpdate'
        ];
    }

    /**
     * Исследует обновление статуса тест-драйва на предмет необходимости отправки уведомления тг-боту водителей
     *
     * @param UpdateDriveEvent $event
     * @return bool
     */
    public function processDriveUpdate(UpdateDriveEvent $event): bool
    {
        $drive = $this->driveRepository->find($event->getDriveId());

        if (!$drive) {
            $this->logger->error("Не найдена поездка для обработки {$event->getDriveId()}");
            return false;
        }

        $schedule = $drive->getSchedule();
        if ($drive->getState() === Drive::STATE_NEW) {
            $this->logger->info("Пришла новая поездка {$drive->getId()}");
            if ($schedule->isReadyForSurvey()) {
                // расписание удовлетворяет всем признакам для запуска опроса
                return $this->telegramBot->sendNewSurveyNotification($schedule);
            }
        }

        if ($drive->getState() === Drive::STATE_CANCELLED) {
            $this->logger->info("Пришла отмена по поездке {$drive->getId()}");
            if ($schedule->isReadyForSurvey() && $schedule->getActiveDrives()->count() === 0) {
                return $this->telegramBot->sendStopSurveyNotification($schedule);
            }
        }

        return true;
    }
}
