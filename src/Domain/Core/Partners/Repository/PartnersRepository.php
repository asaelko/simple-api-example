<?php

namespace App\Domain\Core\Partners\Repository;

use CarlBundle\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с партнерами
 */
class PartnersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }

    /**
     * Отдает всех партнеров для переданной категории отображения
     *
     * @param int $showIn
     *
     * @return array
     */
    public function getByCategory(int $showIn): array
    {
        return $this->_em->createQueryBuilder()
            ->select('partners')
            ->from(Partner::class, 'partners')
            ->orderBy('partners.showPriority')
            ->where('partners.showInSection = :section')
            ->andWhere('partners.statusId = 1')
            ->setParameter('section', $showIn)
            ->getQuery()
            ->getResult();
    }

    /**
     * Отдает всех активных партнеров
     *
     * @return array
     */
    public function getActive(): array
    {
        return $this->_em->createQueryBuilder()
            ->select('partners')
            ->from(Partner::class, 'partners')
            ->orderBy('partners.showPriority')
            ->andWhere('partners.statusId = 1')
            ->getQuery()
            ->getResult();
    }
}
