<?php

namespace App\Domain\Core\LongDrive\Repository;

use App\Entity\LongDrive\LongDriveRequest;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Model\Model;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LongDriveRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method LongDriveRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method LongDriveRequest[]    findAll()
 * @method LongDriveRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LongDriveRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LongDriveRequest::class);
    }

    public function list(int $limit, int $offset, ?int $fromTime = null, ?int $toTime = null, ?array $partners = []): array
    {
        $this->getEntityManager()->getFilters()->disable('softdeleteable');

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select(['ld', 'client'])
            ->from(LongDriveRequest::class, 'ld')
            ->leftJoin('ld.client', 'client')
            ->where('ld.deletedAt is null');
        if ($fromTime) {
            $qb->andWhere('ld.createdAt >= :from')
                ->setParameter('from',  (new DateTime())->setTimestamp($fromTime));
        }
        if ($toTime) {
            $qb->andWhere('ld.createdAt <= :to')
                ->setParameter('to', (new DateTime())->setTimestamp($toTime));
        }
        if ($partners) {
            $qb->andWhere('ld.partner in (:partners)')
                ->setParameter('partners', $partners);
        }

        $count = clone $qb;

        $items = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('ld.createdAt', 'desc')
            ->getQuery()
            ->getResult()
        ;

        try {
            $total = $count->select('COUNT(ld.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            $total = count($items);
        }

        $this->getEntityManager()->getFilters()->enable('softdeleteable');

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
