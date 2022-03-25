<?php


namespace App\Domain\Notifications\Messages\Drive\Handler;


use App\Domain\Notifications\Messages\Drive\Message\OnFeedbackDriveCheckVideoUrlSlackNotificationMessage;
use CarlBundle\Repository\DriveRepository;
use CarlBundle\Service\SlackNotificatorService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class OnFeedbackDriveCheckVideoUrlSlackNotificationHandler implements MessageHandlerInterface
{
    private DriveRepository $driveRepository;

    private SlackNotificatorService $slackNotificator;

    public function __construct(
        DriveRepository $driveRepository,
        SlackNotificatorService $slackNotificator
    )
    {
        $this->driveRepository = $driveRepository;
        $this->slackNotificator = $slackNotificator;
    }

    public function __invoke(OnFeedbackDriveCheckVideoUrlSlackNotificationMessage $message)
    {
        $drive = $this->driveRepository->find($message->getDriveId());
        if ($drive && !$drive->getVideoUrl()) {
            $this->slackNotificator->sendNoVideoNotification($drive);
        }
    }
}