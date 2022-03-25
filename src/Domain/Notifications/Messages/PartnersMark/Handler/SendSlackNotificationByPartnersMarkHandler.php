<?php


namespace App\Domain\Notifications\Messages\PartnersMark\Handler;


use App\Domain\Core\Partners\Helper\PartnersMarkHelper;
use App\Domain\Notifications\Messages\PartnersMark\Message\SendSlackNotificationByPartnersMarkMessage;
use App\Entity\PartnersMark;
use CarlBundle\Service\SlackNotificatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendSlackNotificationByPartnersMarkHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;

    private SlackNotificatorService $slackService;

    private PartnersMarkHelper $helper;

    public function __construct(
        EntityManagerInterface $entityManager,
        SlackNotificatorService $slackService,
        PartnersMarkHelper $helper
    )
    {
        $this->entityManager = $entityManager;
        $this->slackService = $slackService;
        $this->helper = $helper;
    }

    public function __invoke(SendSlackNotificationByPartnersMarkMessage $message): bool
    {
        $mark = $this->entityManager->getRepository(PartnersMark::class)->find($message->getMarkId());
        if (!($mark instanceof PartnersMark)) {
            return false;
        }
        $partnerName = $this->helper->getPartnersName($mark);

        $this->slackService->sendPartnerNotification($mark->getClient(), $partnerName, $mark->getMark(), $mark->getComment());

        return true;
    }
}