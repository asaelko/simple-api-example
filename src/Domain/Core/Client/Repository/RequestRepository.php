<?php

namespace App\Domain\Core\Client\Repository;

use App\Entity\LongDrive\LongDriveRequest;
use App\Entity\Subscription\SubscriptionQuery;
use App\Entity\SubscriptionRequest;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use DateInterval;
use DateTime;
use DealerBundle\Entity\DriveOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

class RequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Получаем запросы пользователя на подписку
     *
     * @param Client $client
     * @param bool   $isArchived
     *
     * @return array
     */
    public function getOfferRequests(Client $client, bool $isArchived = false): array
    {
        $this->getEntityManager()->getFilters()->disable('softdeleteable');
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('o', 'client', 'dealer', 'd', 'e', 'm', 'mp', 'model_photos', 'brand')
            ->from(DriveOffer::class, 'o')
            ->leftJoin('o.client', 'client')
            ->leftJoin('o.dealer', 'dealer')
            ->leftJoin('o.dealerCar', 'd')
            ->leftJoin('d.equipment', 'e')
            ->leftJoin('e.model', 'm')
            ->leftJoin('m.brand', 'brand')
            ->leftJoin('m.photos', 'mp')
            ->leftJoin('mp.photo', 'model_photos')
            ->where('o.client = :client')
            ->andWhere('o.deletedAt is null')
            ->setParameter('client', $client)
            ->orderBy('o.createdAt', 'DESC');

        if (!$isArchived) {
            $qb->andWhere('o.expirationAt >= :expirationDate or o.expirationAt is null');
        } else {
            $qb->andWhere('o.expirationAt < :expirationDate');
        }
        $qb->setParameter('expirationDate', (new DateTime));

        $result = $qb->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        $this->getEntityManager()->getFilters()->enable('softdeleteable');
        return $result;
    }

    /**
     * Получаем запросы пользователя на подписку
     *
     * @param Client $client
     * @param bool   $isArchived
     *
     * @return array
     */
    public function getSubscriptionRequests(Client $client, bool $isArchived = false): array
    {
        $qb =  $this->getEntityManager()->createQueryBuilder()
            ->select('s', 'client', 'partner', 'subscription_model','model','mp','model_photos','brand')
            ->from(SubscriptionRequest::class, 's')
            ->leftJoin('s.client', 'client')
            ->leftJoin('s.partner', 'partner')
            ->leftJoin('s.model', 'subscription_model')
            ->leftJoin('subscription_model.model', 'model')
            ->leftJoin('model.brand', 'brand')
            ->leftJoin('model.photos', 'mp')
            ->leftJoin('mp.photo', 'model_photos')
            ->where('s.client = :client')
            ->setParameter('client', $client)
            ->orderBy('s.createdAt', 'DESC');

        if (!$isArchived) {
            $qb->andWhere('s.createdAt >= :expirationDate');
        } else {
            $qb->andWhere('s.createdAt < :expirationDate');
        }
        $qb->setParameter('expirationDate', (new DateTime)->sub(new DateInterval('P7D')));

        return $qb->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    /**
     * Получаем запросы пользователя на заявку на подписку
     *
     * @param Client $client
     * @param bool   $isArchived
     *
     * @return array
     */
    public function getSubscriptionQueries(Client $client, bool $isArchived = false): array
    {
        $qb =  $this->getEntityManager()->createQueryBuilder()
            ->select('s', 'client', 'model','mp','model_photos','brand')
            ->from(SubscriptionQuery::class, 's')
            ->leftJoin('s.client', 'client')
            ->leftJoin('s.model', 'model')
            ->leftJoin('model.brand', 'brand')
            ->leftJoin('model.photos', 'mp')
            ->leftJoin('mp.photo', 'model_photos')
            ->where('s.client = :client')
            ->setParameter('client', $client)
            ->orderBy('s.createdAt', 'DESC');

        if (!$isArchived) {
            $qb->andWhere('s.createdAt >= :expirationDate');
        } else {
            $qb->andWhere('s.createdAt < :expirationDate');
        }
        $qb->setParameter('expirationDate', (new DateTime)->sub(new DateInterval('P7D')));

        return $qb->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    /**
     * Получаем поездки пользователя
     *
     * @param Client $client
     * @param bool   $isArchived
     *
     * @return array
     */
    public function getTestDriveRequests(Client $client, bool $isArchived = false): array
    {
        $this->getEntityManager()->getFilters()->disable('softdeleteable');

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select([
                'drive',
                'client',
                'driveRate',
                'feedback',
                'drive_photos',
                'photos',
                'schedule',
                'car',
                'driver',
                'equipment',
                'model',
                'mp',
                'model_photos',
                'brand'
            ])
            ->from(Drive::class, 'drive')
            ->leftJoin('drive.client', 'client')
            ->leftJoin('drive.schedule', 'schedule')
            ->leftJoin('drive.driveRate', 'driveRate')
            ->leftJoin('drive.feedback', 'feedback')
            ->leftJoin('drive.drivePhotos','drive_photos')
            ->leftJoin('drive_photos.photo', 'photos')
            ->leftJoin('schedule.car', 'car')
            ->leftJoin('schedule.driver', 'driver')
            ->leftJoin('car.equipment', 'equipment')
            ->leftJoin('equipment.model', 'model')
            ->leftJoin('model.brand', 'brand')
            ->leftJoin('model.photos', 'mp')
            ->leftJoin('mp.photo', 'model_photos')
            ->where('drive.client = :client')
            ->andWhere('drive.deletedAt is null')
            ->setParameter('client', $client)
            ->orderBy('drive.start', 'DESC');

        if ($isArchived) {
            $qb->andWhere('((drive.state = 7 AND :expirationDate >= drive.actualStop) OR (drive.state = 5))');
        } else {
            $qb->andWhere('(drive.state <> 5 AND (drive.state <> 7 OR :expirationDate < drive.actualStop))');
        }
        $qb->setParameter('expirationDate', (new DateTime)->sub(new DateInterval('P7D')));

        $result = $qb->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        $this->getEntityManager()->getFilters()->enable('softdeleteable');

        return $result;
    }

    /**
     * Получаем поездки пользователя
     *
     * @param Client $client
     * @param bool   $isArchived
     *
     * @return array
     */
    public function getLongDriveRequests(Client $client, bool $isArchived = false): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('request', 'client', 'partner', 'ldm_model', 'model', 'brand')
            ->from(LongDriveRequest::class, 'request')
            ->leftJoin('request.client', 'client')
            ->leftJoin('request.partner', 'partner')
            ->leftJoin('request.model', 'ldm_model')
            ->leftJoin('ldm_model.model', 'model')
            ->leftJoin('model.brand', 'brand')
            ->where('request.client = :client')
            ->setParameter('client', $client)
            ->orderBy('request.createdAt', 'DESC');

        if (!$isArchived) {
            $qb->andWhere('request.createdAt >= :expirationDate');
        } else {
            $qb->andWhere('request.createdAt < :expirationDate');
        }
        $qb->setParameter('expirationDate', (new DateTime)->sub(new DateInterval('P7D')));

        return $qb->getQuery()
            ->getResult();
    }
}
