<?php

namespace App\Domain\Core\Web\Repository;

use App\Entity\Web\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function persist(Page $page): void
    {
        $this->_em->persist($page);
    }

    public function remove(Page $page): void
    {
        $this->_em->remove($page);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }
}
