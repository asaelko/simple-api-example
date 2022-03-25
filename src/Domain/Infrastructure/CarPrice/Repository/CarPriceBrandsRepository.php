<?php

namespace App\Domain\Infrastructure\CarPrice\Repository;

use App\Entity\CarPriceBrands;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CarPriceBrands|null find($id, $lockMode = null, $lockVersion = null)
 * @method CarPriceBrands|null findOneBy(array $criteria, array $orderBy = null)
 * @method CarPriceBrands[]    findAll()
 * @method CarPriceBrands[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarPriceBrandsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarPriceBrands::class);
    }

    public function mapCarPriceModel(string $name): array
    {
        $qb = $this->createQueryBuilder('c');
        return $qb->where('c.brandName like :name')
            ->setParameter('name', '' . $name . '%')
            ->getQuery()
            ->getResult()
            ;
    }
}
