<?php

namespace App\Domain\Infrastructure\CarPrice\Repository;

use App\Entity\CarPriceBrands;
use App\Entity\CarPriceModels;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CarPriceModels|null find($id, $lockMode = null, $lockVersion = null)
 * @method CarPriceModels|null findOneBy(array $criteria, array $orderBy = null)
 * @method CarPriceModels[]    findAll()
 * @method CarPriceModels[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarPriceModelsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarPriceModels::class);
    }

    public function mapModel(CarPriceBrands $brand, string $modelName)
    {
        $qb = $this->createQueryBuilder('m');
        return $qb
            ->andWhere('m.brand = :brand')
            ->andWhere('m.modelName like :name')
            ->setParameter('brand', $brand)
            ->setParameter('name', '%' . $modelName . '%')
            ->getQuery()
            ->getResult()
            ;
    }
}
