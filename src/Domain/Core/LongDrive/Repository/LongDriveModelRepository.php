<?php

namespace App\Domain\Core\LongDrive\Repository;

use App\Domain\WebSite\Catalog\Request\ListLongDrivesRequest;
use App\Entity\LongDrive\LongDriveModel;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\Model\Model;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LongDriveModel|null find($id, $lockMode = null, $lockVersion = null)
 * @method LongDriveModel|null findOneBy(array $criteria, array $orderBy = null)
 * @method LongDriveModel[]    findAll()
 * @method LongDriveModel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LongDriveModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LongDriveModel::class);
    }

    public function list(int $limit, int $offset, array $partners = [], array $brands = [], array $models = []): array
    {
        $qb = $this->createQueryBuilder('ldm');

        if ($partners) {
            $qb->andWhere('ldm.partner in (:partners)')
                ->setParameter('partners', $partners);
        }

        if ($brands) {
            $qb->leftJoin('ldm.model', 'models')
                ->andWhere('models.brand in (:brands)')
                ->setParameter('brands', $brands);
        }

        if ($models) {
            $qb->andWhere('ldm.model IN (:models)')
                ->setParameter('models', $models);
        }

        $count = clone $qb;

        $items = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        try {
            $total = $count
                ->select('COUNT(ldm.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            $total = count($items);
        }

        return ['items' => $items, 'count' => $total];
    }

    /**
     * Получаем список фильтров для стоков
     *
     * @param array $partners
     *
     * @return array
     */
    public function getFilters(array $partners = []): array
    {
        $partnersDataQuery = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT partner.id, partner.name')
            ->from(LongDriveModel::class, 'ldm')
            ->leftJoin('ldm.partner', 'partner');
        if ($partners) {
            $partnersDataQuery->andWhere('ldm.partner IN (:partners)')
                ->setParameter('partners', $partners);
        }
        $partnersData = $partnersDataQuery
            ->orderBy('partner.name', 'ASC')
            ->getQuery()
            ->getArrayResult();


        $modelsDataQuery = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT brand.id as brandId, brand.name as brandName, model.id as modelId, model.name as modelName')
            ->from(LongDriveModel::class, 'ldm')
            ->leftJoin('ldm.model', 'model')
            ->leftJoin('model.brand', 'brand');

        if ($partners) {
            $modelsDataQuery->andWhere('ldm.partner IN (:partners)')
                ->setParameter('partners', $partners);
        }

        $modelsData = $modelsDataQuery
            ->orderBy('brand.name', 'ASC')
            ->addOrderBy('model.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $filters = [];
        foreach($modelsData as $model){
            $filters[$model['brandId']] ??= ['id' => $model['brandId'], 'name' => $model['brandName'], 'models' => []];

            $filters[$model['brandId']]['models'][] = ['id' => $model['modelId'], 'name' => $model['modelName']];
        }

        return ['brands' => array_values($filters), 'partners' => $partnersData];
    }

    public function listCatalogForWebSite(ListLongDrivesRequest $request): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('ldm')
            ->from(LongDriveModel::class, 'ldm')
            ->innerJoin('ldm.model', 'm')
            ->andWhere('m.bodyType is not null');

        if ($request->search) {
            $qb->leftJoin('m.brand', 'b')
                ->where($qb->expr()->orX(
                    "CONCAT(m.name, ' ', b.name) LIKE :word",
                    "CONCAT(b.name, ' ', m.name) LIKE :word"
                ))
                ->setParameter('word', '%' . $request->search . '%', Types::STRING);
        }

        if ($request->brands) {
            $qb->andWhere('m.brand in (:brands)')
                ->setParameter('brands', $request->brands);
        }

        if ($request->models) {
            $qb->andWhere('m.id in (:models)')
                ->setParameter('models', $request->models);
        }

        $count = clone $qb;

        $qb->setFirstResult($request->offset)
            ->setMaxResults($request->limit);

        $items = $qb->getQuery()->getResult();

        try {
            $total = $count->select('COUNT(ldm.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException $e) {
            $total = count($items);
        }

        return ['items' => $items, 'count' => $total];
    }

    /**
     * Формируем список фильтров по моделям для отдачи на веб
     *
     * @param ListLongDrivesRequest $request
     *
     * @return array
     */
    public function listFiltersForWebSite(ListLongDrivesRequest $request): array
    {
        return [
            'brand' => $this->getBrandFiltersForModelCatalog($request),
            'model' => $this->getModelFiltersForModelCatalog($request),
        ];
    }

    /**
     * @param ListLongDrivesRequest $request
     *
     * @return array
     */
    private function getBrandFiltersForModelCatalog(ListLongDrivesRequest $request): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('models')
            ->from(Model::class, 'models')
            ->innerJoin('models.longDrives', 'ldm')
            ->andWhere('models.bodyType is not null');

        // бренды
        $brandsQuery = (clone $qb)->select('distinct brand.id as id, count(ldm.id) as count')
            ->leftJoin('models.brand', 'brand')
            ->groupBy('brand.id');
        $totalBrands = array_column($brandsQuery->getQuery()->getArrayResult(), null, 'id');

        if ($request->models) {
            $brandsQuery->andWhere('models.id in (:models)')->setParameter('models', $request->models);
        }

        $filteredBrands = array_column($brandsQuery->getQuery()->getArrayResult(), null, 'id');

        $filteredBrands ??= $totalBrands;

        $brands = $this->getEntityManager()->createQueryBuilder()
            ->select('brand')
            ->from(Brand::class, 'brand')
            ->where('brand.id IN (:ids)')
            ->setParameter('ids', $totalBrands)
            ->getQuery()
            ->getResult();

        $brands = array_map(static function (Brand $brand) use ($filteredBrands, $request) {
            return [
                'id'       => $brand->getId(),
                'name'     => $brand->getName(),
                'photo'    => $brand->getLogo() ? $brand->getLogo()->getAbsolutePath() : null,
                'active'   => isset($filteredBrands[$brand->getId()]),
                'count'    => isset($filteredBrands[$brand->getId()]) ? $filteredBrands[$brand->getId()]['count'] : 0,
                'selected' => in_array($brand->getId(), $request->brands, true),
            ];
        }, $brands);

        usort($brands, static fn($brandA, $brandB) => $brandB['active'] <=> $brandA['active']);

        return $brands;
    }

    /**
     * @param ListLongDrivesRequest $request
     *
     * @return array
     */
    private function getModelFiltersForModelCatalog(ListLongDrivesRequest $request): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('models')
            ->from(Model::class, 'models')
            ->innerJoin('models.longDrives', 'ldm')
            ->andWhere('models.bodyType is not null');

        // бренды
        $modelsQuery = (clone $qb)->select('distinct models.id as id, count(ldm.id) as count')
            ->groupBy('models.id');
        $totalModels = array_column($modelsQuery->getQuery()->getArrayResult(), null, 'id');

        if ($request->brands) {
            $modelsQuery->andWhere('models.brand in (:brands)')->setParameter('brands', $request->brands);
        }

        $filteredModels = array_column($modelsQuery->getQuery()->getArrayResult(), null, 'id');

        $filteredModels ??= $totalModels;

        $models = $this->getEntityManager()->createQueryBuilder()
            ->select('model')
            ->from(Model::class, 'model')
            ->where('model.id IN (:ids)')
            ->setParameter('ids', $totalModels)
            ->getQuery()
            ->getResult();

        $models = array_map(static function (Model $model) use ($filteredModels, $request) {
            return [
                'id'       => $model->getId(),
                'name'     => $model->getName(),
                'photo'    => $model->getSitePhoto() ? $model->getSitePhoto()->getAbsolutePath() : null,
                'active'   => isset($filteredModels[$model->getId()]),
                'count'    => isset($filteredModels[$model->getId()]) ? $filteredModels[$model->getId()]['count'] : 0,
                'selected' => in_array($model->getId(), $request->models, true),
            ];
        }, $models);

        usort($models, static fn($modelA, $modelB) => $modelB['active'] <=> $modelA['active']);

        return $models;
    }
}
