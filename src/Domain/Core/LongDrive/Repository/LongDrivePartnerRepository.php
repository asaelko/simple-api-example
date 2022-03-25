<?php

namespace App\Domain\Core\LongDrive\Repository;

use App\Entity\LongDrive\LongDrivePartner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LongDrivePartner|null find($id, $lockMode = null, $lockVersion = null)
 * @method LongDrivePartner|null findOneBy(array $criteria, array $orderBy = null)
 * @method LongDrivePartner[]    findAll()
 * @method LongDrivePartner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LongDrivePartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LongDrivePartner::class);
    }

    public function list(int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('ldp');

        $count = clone $qb;

        $items = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        try {
            $total = $count
                ->select('COUNT(ldp.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            $total = count($items);
        }

        return ['items' => $items, 'count' => $total];
    }
}
