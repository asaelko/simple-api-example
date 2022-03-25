<?php

namespace App\Domain\Core\Subscription\Repository;

use App\Entity\SubscriptionRequest;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Model\Model;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SubscriptionRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubscriptionRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubscriptionRequest[]    findAll()
 * @method SubscriptionRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionRequest::class);
    }

    public function list(int $limit, int $offset, ?int $fromTime = null, ?int $toTime = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select(['s', 'client'])
            ->leftJoin('s.client', 'client')
            ->where('client.id is not null')
            ->andWhere('client.deletedAt is null');

        if ($fromTime) {
            $qb->andWhere('s.createdAt >= :from')
                ->setParameter('from',  (new DateTime())->setTimestamp($fromTime));
        }
        if ($toTime) {
            $qb->andWhere('s.createdAt <= :to')
                ->setParameter('to', (new DateTime())->setTimestamp($toTime));
        }

        $count = clone $qb;

        $items = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('s.createdAt', 'desc')
            ->getQuery()
            ->getResult()
        ;

        try {
            $total = $count->select('COUNT(s.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            $total = count($items);
        }

        return ['items' => $items, 'count' => $total];
    }

    public function getOldRequestByModel(Model $model, Client $client)
    {
        $qb = $this->createQueryBuilder('r');
        return $qb
            ->andWhere('r.client = :client')
            ->join('r.auto', 'a')
            ->andWhere('a.model = :model')
            ->setParameter('client', $client)
            ->setParameter('model', $model)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
        ;
    }
}
