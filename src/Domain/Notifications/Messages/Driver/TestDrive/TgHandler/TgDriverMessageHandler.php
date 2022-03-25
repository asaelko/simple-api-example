<?php


namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgHandler;


use App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage\SendTelegramMessageByDriveInterface;
use CarlBundle\Entity\Driver;
use CarlBundle\Service\DynamicSchedule\MessageSender\TelegramMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class TgDriverMessageHandler implements MessageHandlerInterface
{
    private TelegramMessageSender $telegramService;

    private EntityManagerInterface $entityManager;

    public function __construct(
        TelegramMessageSender $telegramService,
        EntityManagerInterface $entityManager
    )
    {
        $this->telegramService = $telegramService;
        $this->entityManager = $entityManager;
    }

    public function __invoke(SendTelegramMessageByDriveInterface $message)
    {
        $driver = $this->entityManager->getRepository(Driver::class)->find($message->getDriver());
        if (!$driver) {
            return;
        }
        $chatId = $driver->getTelegramChatId();
        if (!$chatId) {
            return;
        }
        try {
            $this->telegramService->sendMessageToTelegram(
                $message->getText(),
                $chatId
            );
        } catch (\Exception $e) {
            return;
        }
    }
}