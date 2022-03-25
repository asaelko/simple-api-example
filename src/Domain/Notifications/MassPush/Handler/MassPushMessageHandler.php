<?php

namespace App\Domain\Notifications\MassPush\Handler;

use App\Domain\Notifications\MassPush\Event\MassPushMessageEvent;
use App\Domain\Notifications\MassPush\MassPushManager;
use App\Repository\Notifications\MassPush\MassPushNotificationRepository;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Хэндлер события масс-пуша пользователям
 */
class MassPushMessageHandler implements MessageHandlerInterface
{
    private MassPushNotificationRepository $notificationRepository;
    private MassPushManager $massPushManager;

    public function __construct(
        MassPushManager $massPushManager,
        MassPushNotificationRepository $notificationRepository
    )
    {
        $this->massPushManager = $massPushManager;
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * @param MassPushMessageEvent $massPushEvent
     * @return bool
     * @throws GuzzleException
     * @throws JsonException
     */
    public function __invoke(MassPushMessageEvent $massPushEvent): bool
    {
        $massPushNotification = $this->notificationRepository->find($massPushEvent->getMassPushNotificationId());

        if (!$massPushNotification) {
            // масспуш был удален?
            return true;
        }

        if ($massPushNotification->getSendDate()->getTimestamp() - time() > 300) {
            // масспуш был перенесен, отрубаемся
            return true;
        }

        return $this->massPushManager->send($massPushNotification);
    }
}
