<?php


namespace App\Domain\Notifications\Messages\Dealer\Handler;


use App\Domain\Notifications\Messages\Dealer\Message\DealerTestDriveSlackNotificationMessage;
use App\Entity\TestDriveRequest;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Dealer;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Service\SlackNotificatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DealerTestDriveSlackNotificationHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;

    private SlackNotificatorService $slackNotificationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SlackNotificatorService $slackNotificatorService
    )
    {
        $this->entityManager = $entityManager;
        $this->slackNotificationService = $slackNotificatorService;
    }

    public function __invoke(DealerTestDriveSlackNotificationMessage $message): bool
    {
        $client = $this->entityManager->getRepository(Client::class)->find($message->getUserId());
        if (!$client) {
            return false;
        }
        $model = $this->entityManager->getRepository(Model::class)->find($message->getModelId());
        if (!$model) {
            return false;
        }
        $dealer = $this->entityManager->getRepository(Dealer::class)->find($message->getDealerId());
        if (!$dealer) {
            return false;
        }
        if ($message->getEvent() == TestDriveRequest::STATUS_DECLINE) {
            $this->slackNotificationService->sendClientDeclineDealerTestDriveNotification(
                $client,
                $model,
                $dealer,
                $message->getDate()
            );
            return true;
        }
        $this->slackNotificationService->sendClientCreateDealerTestDriveNotification(
            $client,
            $model,
            $dealer,
            $message->getDate()
        );
        return true;
    }
}