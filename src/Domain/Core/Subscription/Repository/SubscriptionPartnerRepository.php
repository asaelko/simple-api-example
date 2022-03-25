<?php

namespace App\Domain\Core\Subscription\Repository;

use App\Entity\SubscriptionPartner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SubscriptionPartner|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubscriptionPartner|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubscriptionPartner[]    findAll()
 * @method SubscriptionPartner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionPartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPartner::class);
    }

    public function list(int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('s');

        $count = clone $qb;

        $items = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        try {
            $total = $count
                ->select('COUNT(s.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            $total = count($items);
        }

        return ['items' => $items, 'count' => $total];
    }
}
