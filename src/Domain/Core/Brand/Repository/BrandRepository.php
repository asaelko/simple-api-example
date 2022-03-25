<?php

namespace App\Domain\Core\Brand\Repository;

use App\Domain\Core\Brand\Repository\DTO\ListFilterRequest;
use CarlBundle\Entity\Brand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Brand|null find($id, $lockMode = null, $lockVersion = null)
 * @method Brand|null findOneBy(array $criteria, array $orderBy = null)
 * @method Brand[] findAll()
 * @method Brand[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BrandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    /**
     * Выгружаем бренды вместе с приаттаченными дилерами, моделями, комплектациями и машинами
     * Фильтруем по клиенту
     *
     * @param ListFilterRequest $filterRequest
     * @return array
     */
    public function getList(ListFilterRequest $filterRequest): array
    {
        $qbItems = $this->getEntityManager()->createQueryBuilder();

        $qbItems
            ->select(['brands', 'brandPhotos'])
            ->from(Brand::class, 'brands')
            ->innerJoin('brands.brandCities', 'brandCities')
            ->innerJoin('brands.brandPhotos', 'brandPhotos', Join::WITH, 'brandPhotos.type = \'logo\'')
            ->where('brandCities.state IN (:states)')
            ->setParameter('states', [Brand::ENABLED, Brand::COMING_SOON])
            ->orderBy('brands.name', 'asc')
        ;

        if ($filterRequest->cities) {
            $qbItems->andWhere('brandCities.city IN (:cities)')
                ->setParameter('cities', $filterRequest->cities);
        }

        if ($filterRequest->brands) {
            $qbItems->andWhere('brands.id IN (:brands)')
                ->setParameter('brands', $filterRequest->brands);
        }

        return $qbItems->getQuery()->getResult();
    }
}
