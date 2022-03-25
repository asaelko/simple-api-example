<?php

namespace App\Domain\Notifications\Messages\Drive\Handler;

use App\Domain\Notifications\Messages\Drive\Message\OnDriveUpCheckCameraNotificationMessage;
use CarlBundle\Entity\Drive;
use CarlBundle\Repository\DriveRepository;
use CarlBundle\Service\DynamicSchedule\MessageSender\TelegramMessageSender;
use CarlBundle\Service\IvideonService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Проверяет, включена ли камера перед началом поездки
 */
class OnDriveUpCheckCameraNotificationHandler implements MessageHandlerInterface
{
    private IvideonService $ivideonService;

    private TelegramMessageSender $telegramSender;

    private DriveRepository $driveRepository;

    public function __construct(
        IvideonService $ivideonService,
        TelegramMessageSender $telegramMessageSender,
        DriveRepository $driveRepository
    )
    {
        $this->ivideonService = $ivideonService;
        $this->telegramSender = $telegramMessageSender;
        $this->driveRepository = $driveRepository;
    }

    public function __invoke(OnDriveUpCheckCameraNotificationMessage $message): bool
    {
        $drive = $this->driveRepository->find($message->getDriveId());
        if (!$drive || $drive->getState() !== Drive::STATE_DRIVE_UP) {
            return false;
        }

        if ($driver = $drive->getDriver()) {
            $cameraId = $drive->getCar()->getCameraId() ?? $driver->getCameraId();

            if (!$cameraId || !$this->ivideonService->isCameraOnline($cameraId)) {
                $this->telegramSender->sendDriverTurnUpCameraNotification($driver);
            }
        }
        return true;
    }
}
