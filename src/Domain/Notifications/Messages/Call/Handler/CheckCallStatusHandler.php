<?php


namespace App\Domain\Notifications\Messages\Call\Handler;


use App\Domain\Infrastructure\IpTelephony\Uis\Service\UisService;
use App\Domain\Notifications\Messages\Call\Message\CheckCallStatusMessage;
use CarlBundle\Entity\Drive;
use CarlBundle\Service\DynamicSchedule\MessageSender\TelegramMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CheckCallStatusHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;

    private UisService $uisService;

    private TelegramMessageSender $telegramSender;

    public function __construct(
        EntityManagerInterface $entityManager,
        UisService $uisService,
        TelegramMessageSender $telegramSender
    )
    {
        $this->entityManager = $entityManager;
        $this->uisService = $uisService;
        $this->telegramSender = $telegramSender;
    }

    public function __invoke(CheckCallStatusMessage $message): bool
    {
        /** @var Drive|null $drive */
        $drive = $this->entityManager->getRepository(Drive::class)->find($message->getDriveId());
        if (!$drive) {
            return false;
        }

        $call = $this->uisService->getCallInformation($message->getCallId(), new \DateTime('-20 minute'), new \DateTime());

        if ($call) {
            return true;
        }

        if ($drive->getDriver()->getTelegramChatId()) {
            $this->telegramSender->sendWhenHaveLostCall($drive);
        }

        return true;
    }
}