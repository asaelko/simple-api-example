<?php

namespace App\Domain\Core\Subscription\Repository;

use App\Domain\WebSite\Catalog\Request\ListSubscriptionsRequest;
use App\Entity\SubscriptionModel;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\Model\Model;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SubscriptionModel|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubscriptionModel|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubscriptionModel[]    findAll()
 * @method SubscriptionModel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionModel::class);
    }

    public function list(int $limit, int $offset, array $partners = [], array $brands = [], array $models = []): array
    {
        $qb = $this->createQueryBuilder('s');

        if ($partners) {
            $qb->andWhere('s.partner in (:partners)')
                ->setParameter('partners', $partners);
        }

        if ($brands) {
            $qb->leftJoin('s.model', 'models')
                ->andWhere('models.brand in (:brands)')
                ->setParameter('brands', $brands);
        }

        if ($models) {
            $qb->andWhere('s.model IN (:models)')
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
                ->select('COUNT(s.id)')
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
            ->from(SubscriptionModel::class, 's')
            ->leftJoin('s.partner', 'partner');
        if ($partners) {
            $partnersDataQuery->andWhere('s.partner IN (:partners)')
                ->setParameter('partners', $partners);
        }
        $partnersData = $partnersDataQuery
            ->orderBy('partner.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $modelsDataQuery = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT brand.id as brandId, brand.name as brandName, model.id as modelId, model.name as modelName')
            ->from(SubscriptionModel::class, 's')
            ->leftJoin('s.model', 'model')
            ->leftJoin('model.brand', 'brand');

        if ($partners) {
            $modelsDataQuery->andWhere('s.partner IN (:partners)')
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

    public function listCatalogForWebSite(ListSubscriptionsRequest $request): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('s')
            ->from(SubscriptionModel::class, 's')
            ->innerJoin('s.model', 'm')
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
            $total = $count->select('COUNT(s.id)')
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
     * @param ListSubscriptionsRequest $request
     *
     * @return array
     */
    public function listFiltersForWebSite(ListSubscriptionsRequest $request): array
    {
        return [
            'brand' => $this->getBrandFiltersForModelCatalog($request),
            'model' => $this->getModelFiltersForModelCatalog($request),
        ];
    }

    /**
     * @param ListSubscriptionsRequest $request
     *
     * @return array
     */
    private function getBrandFiltersForModelCatalog(ListSubscriptionsRequest $request): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('models')
            ->from(Model::class, 'models')
            ->innerJoin('models.subscriptions', 's')
            ->andWhere('models.bodyType is not null');

        // бренды
        $brandsQuery = (clone $qb)->select('distinct brand.id as id, count(s.id) as count')
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
     * @param ListSubscriptionsRequest $request
     *
     * @return array
     */
    private function getModelFiltersForModelCatalog(ListSubscriptionsRequest $request): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('models')
            ->from(Model::class, 'models')
            ->innerJoin('models.subscriptions', 's')
            ->andWhere('models.bodyType is not null');

        // бренды
        $modelsQuery = (clone $qb)->select('distinct models.id as id, count(s.id) as count')
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
