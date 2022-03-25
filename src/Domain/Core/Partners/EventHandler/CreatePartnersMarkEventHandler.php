<?php

namespace App\Domain\Core\Partners\EventHandler;

use App\Domain\EventBus\Dealer\Callback\CallbackClosedEvent;
use App\Domain\EventBus\Leasing\LeasingRequestCreatedEvent;
use App\Domain\EventBus\Subscription\SubscriptionQueryCreatedEvent;
use App\Domain\EventBus\Subscription\SubscriptionRequestCreatedEvent;
use App\Entity\PartnersMark;
use App\Entity\Subscription\SubscriptionQuery;
use App\Entity\SubscriptionRequest;
use CarlBundle\Entity\Leasing\LeasingRequest;
use CarlBundle\Entity\Loan\LoanApplication;
use CarlBundle\EventBus\DealerOffer\Event\OfferRespondedEvent;
use CarlBundle\EventBus\Loan\Event\NewLoanRequestCreatedEvent;
use DealerBundle\Entity\CallbackAction;
use DealerBundle\Entity\DriveOffer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

/**
 * Создаем заявки на оценку партнеров по событиям приложения
 */
class CreatePartnersMarkEventHandler implements MessageSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $partnersMarkLogger
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $partnersMarkLogger;
    }

    /**
     * @inheritDoc
     */
    public static function getHandledMessages(): iterable
    {
        yield NewLoanRequestCreatedEvent::class => [
            'method' => 'onNewLoanRequestCreatedAction'
        ];

        yield OfferRespondedEvent::class => [
            'method' => 'onOfferRespondedAction',
        ];

        yield LeasingRequestCreatedEvent::class => [
            'method' => 'onLeasingRequestCreatedAction',
        ];

        yield CallbackClosedEvent::class => [
            'method' => 'onCallbackClosedAction',
        ];

        yield SubscriptionRequestCreatedEvent::class => [
            'method' => 'onSubscriptionRequestCreatedAction',
        ];

        yield SubscriptionQueryCreatedEvent::class => [
            'method' => 'onSubscriptionQueryCreatedAction',
        ];
    }

    /**
     * Создаем заявку на оценку партнера после заявки на кредит
     *
     * @param NewLoanRequestCreatedEvent $event
     */
    public function onNewLoanRequestCreatedAction(NewLoanRequestCreatedEvent $event): void
    {
        $loanApplication = $this->entityManager->getRepository(LoanApplication::class)->find($event->getLoanApplicationId());

        if (!$loanApplication) {
            return;
        }

        $partnerMarkRecord = (new PartnersMark())
            ->setClient($loanApplication->getClient())
            ->setPartnerId($loanApplication->getProvider()->getId())
            ->setPartnerClass(get_class($loanApplication->getProvider()))
            ->setRequestType(PartnersMark::TYPE_LOAN)
            ->setPartnerRequestClass(get_class($loanApplication))
            ->setPartnerRequestId($loanApplication->getId());

        $this->saveMark($partnerMarkRecord);
    }

    /**
     * Создаем заявку на оценку дилера после ответа на оффер
     *
     * @param OfferRespondedEvent $event
     */
    public function onOfferRespondedAction(OfferRespondedEvent $event): void
    {
        $offer = $this->entityManager->getRepository(DriveOffer::class)->find($event->getOfferId());

        if (!$offer) {
            return;
        }

        $partnerMarkRecord = (new PartnersMark())
            ->setClient($offer->getClient())
            ->setPartnerId($offer->getDealer()->getId())
            ->setPartnerClass(get_class($offer->getDealer()))
            ->setRequestType(PartnersMark::TYPE_DRIVE_OFFER)
            ->setPartnerRequestClass(get_class($offer))
            ->setPartnerRequestId($offer->getId());

        $this->saveMark($partnerMarkRecord);
    }

    /**
     * Создаем заявку на оценку партнера по лизингу после отправки заявки
     *
     * @param LeasingRequestCreatedEvent $event
     */
    public function onLeasingRequestCreatedAction(LeasingRequestCreatedEvent $event): void
    {
        $request = $this->entityManager->getRepository(LeasingRequest::class)->find($event->leasingRequestId);

        if (!$request) {
            return;
        }

        $partnerMarkRecord = (new PartnersMark())
            ->setClient($request->getClient())
            ->setPartnerId($request->getProvider()->getId())
            ->setPartnerClass(get_class($request->getProvider()))
            ->setRequestType(PartnersMark::TYPE_LEASING)
            ->setPartnerRequestClass(get_class($request))
            ->setPartnerRequestId($request->getId());

        $this->saveMark($partnerMarkRecord);
    }

    /**
     * Создаем заявку на оценку дилера после совершенного коллбека
     *
     * @param CallbackClosedEvent $event
     */
    public function onCallbackClosedAction(CallbackClosedEvent $event): void
    {
        $callback = $this->entityManager->getRepository(CallbackAction::class)->find($event->callbackId);

        if (!$callback) {
            return;
        }

        $partnerMarkRecord = (new PartnersMark())
            ->setClient($callback->getClient())
            ->setPartnerId($callback->getDealer() ? $callback->getDealer()->getId() : $callback->getDealerCar()->getId())
            ->setPartnerClass(get_class($callback->getDealerCar() ?? $callback->getDealer()))
            ->setRequestType(PartnersMark::TYPE_CALLBACK)
            ->setPartnerRequestClass(get_class($callback))
            ->setPartnerRequestId($callback->getId());

        $this->saveMark($partnerMarkRecord);
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

        $partners = new PartnersMark();

        $partners->setClient($request->getClient());
        $partners->setPartnerId($request->getPartner()->getId());
        $partners->setPartnerClass(get_class($request->getPartner()));
        $partners->setRequestType(PartnersMark::TYPE_SUBSCRIPTION);
        $partners->setPartnerRequestClass(get_class($request));
        $partners->setPartnerRequestId($request->getId());

        $this->saveMark($partners);
    }

    /**
     * Создаем заявку на оценку качества партнера по подписке
     *
     * @param SubscriptionRequestCreatedEvent $event
     */
    public function onSubscriptionQueryCreatedAction(SubscriptionQueryCreatedEvent $event): void
    {
        $request = $this->entityManager->getRepository(SubscriptionQuery::class)->find($event->requestId);

        if (!$request) {
            return;
        }

        $partners = new PartnersMark();

        $partners->setClient($request->getClient());
        $partners->setPartnerId(1);
        $partners->setPartnerClass('CARL');
        $partners->setRequestType(PartnersMark::TYPE_SUBSCRIPTION_QUERY);
        $partners->setPartnerRequestClass(get_class($request));
        $partners->setPartnerRequestId($request->getId());

        $this->saveMark($partners);
    }

    /**
     * Сохраняем запись на оценку и логируем событие для дебага
     *
     * @param PartnersMark $partnerMarkRecord
     */
    private function saveMark(PartnersMark $partnerMarkRecord): void {
        $this->entityManager->persist($partnerMarkRecord);
        $this->entityManager->flush();

        $this->logger->info(
            sprintf(
                'Added mark request: %s %s',
                $partnerMarkRecord->getPartnerRequestClass(),
                $partnerMarkRecord->getPartnerRequestId()
            )
        );
    }
}
