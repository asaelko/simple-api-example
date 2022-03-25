<?php

namespace App\Domain\Core\Client\Service;

use App\Domain\Core\Client\Repository\RequestRepository;
use App\Domain\Core\Client\Service\RequestDTO\AbstractRequestDTO;
use App\Domain\Core\Client\Service\RequestDTO\LongDriveDTO;
use App\Domain\Core\Client\Service\RequestDTO\OfferRequestDTO;
use App\Domain\Core\Client\Service\RequestDTO\SubscriptionQueryDTO;
use App\Domain\Core\Client\Service\RequestDTO\SubscriptionRequestDTO;
use App\Domain\Core\Client\Service\RequestDTO\TestDriveDTO;
use App\Domain\Core\System\Service\Security;
use App\Entity\LongDrive\LongDriveRequest;
use App\Entity\PartnersMark;
use App\Entity\Subscription\SubscriptionQuery;
use App\Entity\SubscriptionRequest;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use DealerBundle\Entity\DriveOffer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RequestService
{
    private Security $security;
    private RequestRepository $requestRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        Security               $security,
        RequestRepository      $requestRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->security = $security;
        $this->requestRepository = $requestRepository;
        $this->entityManager = $entityManager;
    }

    public function getAll(bool $isArchived = false): array
    {
        $data = array_merge($this->getDrives($isArchived), $this->getRequests($isArchived));

        usort(
            $data,
            static fn($requestA, $requestB) => $requestA['datetime'] <=> $requestB['datetime']
        );

        return $data;
    }

    /**
     * Получаем список будущих и пройденных поездок и лонг-драйвов
     *
     * @return array
     */
    public function getDrives(bool $isArchived = false): array
    {
        $drives = array_merge(
            $this->getTestDrives($isArchived),
            $this->getLongDrives($isArchived)
        );

        usort(
            $drives,
            static fn(AbstractRequestDTO $requestA, AbstractRequestDTO $requestB) => $requestA->dateTime() <=> $requestB->dateTime()
        );

        return array_map(static fn(AbstractRequestDTO $drive) => [
            'type'           => $drive->type(),
            'datetime'       => $drive->dateTime(),
            $drive->type() => $drive,
        ], $drives);
    }

    /**
     * Получаем список запросов пользователя в приложении
     *
     * @param bool $isArchived
     *
     * @return array
     */
    public function getRequests(bool $isArchived = false): array
    {
        $requests = array_merge(
            $this->getOfferRequests($isArchived),
            $this->getSubscriptionRequests($isArchived),
            $this->getSubscriptionQueries($isArchived),
        );

        usort(
            $requests,
            static fn(AbstractRequestDTO $requestA, AbstractRequestDTO $requestB) => $requestA->dateTime() <=> $requestB->dateTime()
        );

        return array_map(static fn(AbstractRequestDTO $request) => [
            'type'           => $request->type(),
            'datetime'       => $request->dateTime(),
            $request->type() => $request,
        ], $requests);
    }

    private function getTestDrives(bool $isArchived = false): array
    {
        $drives = $this->requestRepository->getTestDriveRequests($this->getClient(), $isArchived);

        return array_map(static fn(Drive $drive) => new TestDriveDTO($drive), $drives);
    }

    private function getLongDrives(bool $isArchived = false): array
    {
        $drives = $this->requestRepository->getLongDriveRequests($this->getClient(), $isArchived);

        return array_map(static fn(LongDriveRequest $drive) => new LongDriveDTO($drive), $drives);;
    }

    /**
     * Получаем список запрошенных КП
     *
     * @param bool $isArchived
     *
     * @return array
     */
    private function getOfferRequests(bool $isArchived = false): array
    {
        $offers = $this->requestRepository->getOfferRequests($this->getClient(), $isArchived);

        return array_map(static fn(DriveOffer $offer) => new OfferRequestDTO($offer), $offers);
    }

    /**
     * Получаем список запросов на подписку
     *
     * @param bool $isArchived
     *
     * @return array
     */
    private function getSubscriptionRequests(bool $isArchived = false): array
    {
        $subscriptions = $this->requestRepository->getSubscriptionRequests($this->getClient(), $isArchived);

        $marks = $this->entityManager->getRepository(PartnersMark::class)->findBy([
            'client'      => $this->getClient(),
            'requestType' => PartnersMark::TYPE_SUBSCRIPTION,
        ]);

        $marks = array_column($marks, null, 'partnerRequestId');
        $indexedMarks = [];
        array_walk($marks, static function (PartnersMark $val) use (&$indexedMarks) {
            $indexedMarks[$val->getPartnerRequestId()] = $val;
        });

        return array_map(static fn(SubscriptionRequest $request) => new SubscriptionRequestDTO($request, $indexedMarks[$request->getId()] ?? null), $subscriptions);
    }

    /**
     * Получаем список запросов на подписку
     *
     * @param bool $isArchived
     *
     * @return array
     */
    private function getSubscriptionQueries(bool $isArchived = false): array
    {
        $subscriptions = $this->requestRepository->getSubscriptionQueries($this->getClient(), $isArchived);

        $marks = $this->entityManager->getRepository(PartnersMark::class)->findBy([
            'client'      => $this->getClient(),
            'requestType' => PartnersMark::TYPE_SUBSCRIPTION_QUERY,
        ]);

        $marks = array_column($marks, null, 'partnerRequestId');
        $indexedMarks = [];
        array_walk($marks, static function (PartnersMark $val) use (&$indexedMarks) {
            $indexedMarks[$val->getPartnerRequestId()] = $val;
        });

        return array_map(static fn(SubscriptionQuery $request) => new SubscriptionQueryDTO($request, $indexedMarks[$request->getId()] ?? null), $subscriptions);
    }

    private function getClient(): Client
    {
        /** @var Client $client */
        $client = $this->security->getUser();
        if (!$client || !$client->isClient()) {
            throw new AccessDeniedHttpException();
        }

        return $client;
    }
}
