<?php

namespace App\Domain\Core\Subscription\Messenger;

use App\Domain\Core\Subscription\Partner\AudiDriveSubscriptionPartner;
use App\Domain\Core\Subscription\Partner\SkodaDriveSubscriptionPartner;
use App\Domain\Core\Subscription\Partner\TheMashinaSubscriptionPartner;
use App\Domain\EventBus\Subscription\SubscriptionRequestCreatedEvent;
use App\Entity\SubscriptionRequest;
use AppBundle\Service\AppConfig;
use CarlBundle\Service\SlackNotificatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class SubscriptionRequestCreatedEventHandler implements MessageSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private AudiDriveSubscriptionPartner $audiDriveSubscriptionPartner;
    private SkodaDriveSubscriptionPartner $skodaDriveSubscriptionPartner;
    private TheMashinaSubscriptionPartner $mashinaSubscriptionPartner;
    private AppConfig $appConfig;
    private SlackNotificatorService $slackNotificatorService;

    public function __construct(
        AppConfig $appConfig,
        EntityManagerInterface $entityManager,
        SlackNotificatorService $slackNotificatorService,
        AudiDriveSubscriptionPartner $audiDriveSubscriptionPartner,
        SkodaDriveSubscriptionPartner $skodaDriveSubscriptionPartner,
        TheMashinaSubscriptionPartner $mashinaSubscriptionPartner
    )
    {
        $this->appConfig = $appConfig;
        $this->entityManager = $entityManager;
        $this->audiDriveSubscriptionPartner = $audiDriveSubscriptionPartner;
        $this->skodaDriveSubscriptionPartner = $skodaDriveSubscriptionPartner;
        $this->mashinaSubscriptionPartner = $mashinaSubscriptionPartner;
        $this->slackNotificatorService = $slackNotificatorService;
    }

    /**
     * @inheritDoc
     */
    public static function getHandledMessages(): iterable
    {
        yield SubscriptionRequestCreatedEvent::class => [
            'method' => 'onSubscriptionRequestCreatedAction',
        ];
    }

    /**
     * Создаем заявку на оценку качества партнера по подписке
     *
     * @param SubscriptionRequestCreatedEvent $event
     */
    public function onSubscriptionRequestCreatedAction(SubscriptionRequestCreatedEvent $event): void
    {
        $request = $this->entityManager->getRepository(SubscriptionRequest::class)->find($event->requestId);

        if (!$request) {
            return;
        }

        $this->slackNotificatorService->sendNewSubscriptionRequest($request);

        if (!$this->appConfig->isProd()) {
            return;
        }

        $partner = $request->getPartner();

        switch ($partner->getId()) {
            case $this->audiDriveSubscriptionPartner::PARTNER_ID:
                $this->audiDriveSubscriptionPartner->sendLead($request);
                break;
            case $this->skodaDriveSubscriptionPartner::PARTNER_ID:
                $this->skodaDriveSubscriptionPartner->sendLead($request);
                break;
            case $this->mashinaSubscriptionPartner::PARTNER_ID:
                $this->mashinaSubscriptionPartner->sendLead($request);
                break;
        }
    }
}
