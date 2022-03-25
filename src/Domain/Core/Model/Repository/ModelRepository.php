<?php

namespace App\Domain\Core\Model\Repository;

use App\Domain\WebSite\Catalog\Request\ListModelsRequest;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Repository\Model\BodyTypeCatalogRepository;
use CarlBundle\Service\DictionaryService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с моделями автомобилей
 *
 * @method Model|null find($id, $lockMode = null, $lockVersion = null)
 */
class ModelRepository extends ServiceEntityRepository
{
    private DictionaryService $dictionaryService;
    private BodyTypeCatalogRepository $bodyTypeRepository;

    public function __construct(
        ManagerRegistry $registry,
        DictionaryService $dictionaryService,
        BodyTypeCatalogRepository $bodyTypeRepository
    )
    {
        parent::__construct($registry, Model::class);
        $this->dictionaryService = $dictionaryService;
        $this->bodyTypeRepository = $bodyTypeRepository;
    }

    /**
     * Получаем данные по моделям определенных брендов
     *
     * @param array $brandsIds
     *
     * @return array
     */
    public function getRichModelsDataForBrands(array $brandsIds): array
    {
        $brandsIdsString = implode(', ', $brandsIds);

        $query = <<<QUERY
            SELECT
                models.id,
                models.brandId as brand_id,
                models.name,
                models.body_type,
                (models.has_subscription_query OR brands.has_subscription_query) as has_subscription_query,
                test_drives.car_id,
                test_drives.freeScheduleTime as test_drive_time,
                stocks.stocks_count,
                COALESCE(stocks.stock_min_price, (select min(equipments.price) from equipments where equipments.modelId = models.id)) as stock_min_price,
                stocks.stock_max_price,
                subscriptions.subscription_count,
                subscriptions.min_price as subscription_min_price,
                long_drive.long_drives_count,
                long_drive.min_price as long_drive_min_price
            FROM
                models
                left join brands on brands.id = models.brandId
                left join (
                    select 
						cars.id as car_id,
                        cars.freeScheduleTime,
                        equipments.modelId
                    from cars
                    left join equipments on equipments.id = cars.equipmentId
                    where cars.deletedAt is null and equipments.deletedAt is null and cars.status = 1
                    order by cars.freeScheduleTime
                ) as test_drives ON test_drives.modelId = models.id
                LEFT JOIN (
                    SELECT
                        count(dealer_cars.id) AS stocks_count,
                        min(dealer_cars.price) AS stock_min_price,
                        max(dealer_cars.price) AS stock_max_price,
                        dealer_equipments.modelId
                    FROM
                        dealer_cars
                        LEFT JOIN dealer_equipments ON dealer_equipments.id = dealer_cars.equipmentId
                    WHERE
                        dealer_cars.deletedAt IS NULL
                        AND dealer_equipments.deletedAt IS NULL
                        AND dealer_cars.`state` <> 3
                        AND dealer_cars.`price` > 0
                    GROUP BY 
                        dealer_equipments.modelId
                ) AS stocks ON stocks.modelId = models.id
                LEFT JOIN (
                    SELECT 
                        subscription_models.model_id,
                        MIN(subscription_models.price) as min_price,
                        COUNT(subscription_models.id) as subscription_count
                    FROM
                        subscription_models
                    WHERE subscription_models.deleted_at is null
                    GROUP BY 
                        subscription_models.model_id
                ) as subscriptions ON subscriptions.model_id = models.id
                LEFT JOIN (
                    SELECT 
                        long_drive_models.model_id,
                        MIN(long_drive_models.price) as min_price,
                        COUNT(long_drive_models.id) as long_drives_count
                    FROM
                        long_drive_models
                    WHERE long_drive_models.deleted_at is null
                    GROUP BY 
                        long_drive_models.model_id
                ) as long_drive ON long_drive.model_id = models.id
            WHERE models.body_type is not null 
                AND models.brandId IN ({$brandsIdsString})
                AND (test_drives.freeScheduleTime IS NOT NULL 
                        OR stocks.stocks_count > 0 
                         OR subscriptions.subscription_count > 0
                        OR long_drive.long_drives_count > 0
                    )
                AND COALESCE(stocks.stock_min_price, (select min(equipments.price) from equipments where equipments.modelId = models.id)) is not null
            ;
QUERY;

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');
        $rsm->addScalarResult('brand_id', 'brand_id', 'integer');
        $rsm->addScalarResult('name', 'name', 'string');
        $rsm->addScalarResult('body_type', 'body_type', 'integer');
        $rsm->addScalarResult('has_subscription_query', 'has_subscription_query', 'boolean');
        $rsm->addScalarResult('car_id', 'car_id', 'integer');
        $rsm->addScalarResult('test_drive_time', 'test_drive_time', 'datetime');
        $rsm->addScalarResult('stocks_count', 'stocks_count', 'integer');
        $rsm->addScalarResult('stock_min_price', 'stock_min_price', 'integer');
        $rsm->addScalarResult('stock_max_price', 'stock_max_price', 'integer');
        $rsm->addScalarResult('subscription_count', 'subscription_count', 'integer');
        $rsm->addScalarResult('subscription_min_price', 'subscription_min_price', 'integer');
        $rsm->addScalarResult('long_drives_count', 'long_drives_count', 'integer');
        $rsm->addScalarResult('long_drive_min_price', 'long_drive_min_price', 'integer');

        return $this->getEntityManager()->createNativeQuery($query, $rsm)->getArrayResult();
    }

    /**
     * Собираем информацию по стокам для модели
     *
     * @param Model[]|array $models
     *
     * @return array
     */
    public function getStocksDataForModel(array $models): array
    {
        if (!$models) {
            return [];
        }

        $modelsIds = array_map(static fn(Model $model) => $model->getId(), $models);
        $modelsIds = implode(',', $modelsIds);

        $query = <<<QUERY
            SELECT
                        dealer_equipments.modelId,
                        count(dealer_cars.id) AS stocks_count,
                        min(dealer_cars.price) AS stock_min_price,
                        max(dealer_cars.price) AS stock_max_price,
                        max(dealers.has_booking_ability) as has_booking_ability, 
						max(dealers.has_delivery_ability) as has_delivery_ability, 
						max(dealers.has_purchase_ability) as has_purchase_ability   
                    FROM
                        dealer_cars
                        LEFT JOIN dealer_equipments ON dealer_equipments.id = dealer_cars.equipmentId
                        LEFT JOIN dealers on dealer_cars.dealerId = dealers.id
                    WHERE
                    dealer_equipments.modelId IN ({$modelsIds})
                    	AND dealer_cars.deletedAt IS NULL
                        AND dealer_equipments.deletedAt IS NULL
                        AND dealer_cars.`state` <> 3
                        AND dealer_cars.`price` > 0
                    GROUP BY 
                        dealer_equipments.modelId
            		LIMIT 0, 1
            ;
QUERY;

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('modelId', 'id', 'integer');
        $rsm->addScalarResult('stocks_count', 'stocks_count', 'integer');
        $rsm->addScalarResult('stock_min_price', 'stock_min_price', 'integer');
        $rsm->addScalarResult('stock_max_price', 'stock_max_price', 'integer');
        $rsm->addScalarResult('has_booking_ability', 'has_booking_ability', 'boolean');
        $rsm->addScalarResult('has_delivery_ability', 'has_delivery_ability', 'boolean');
        $rsm->addScalarResult('has_purchase_ability', 'has_purchase_ability', 'boolean');

        $data = $this->getEntityManager()->createNativeQuery($query, $rsm)->getArrayResult();

        if (!$data) {
            $pricesQuery = <<<QUERY
            select equipments.modelId, min(equipments.price) as price from equipments where equipments.modelId IN ({$modelsIds}) GROUP BY equipments.modelId
QUERY;
            $rsm = new ResultSetMapping();
            $rsm->addScalarResult('modelId', 'id', 'integer');
            $rsm->addScalarResult('price', 'stock_min_price', 'integer');

            $data = $this->getEntityManager()->createNativeQuery($pricesQuery, $rsm)->getArrayResult();
            return array_column($data, null,'id');
        }

        return array_column($data, null, 'id');
    }

    /**
     * @param Model[]|array $models
     *
     * @return array
     */
    public function getTagsForModels(array $models): array
    {
        if (!$models) {
            return [];
        }
        $modelsIds = array_map(static fn(Model $model) => $model->getId(), $models);
        $modelsIds = implode(',', $modelsIds);

        $leasingQuery = <<<QUERY
            select distinct modelId from leasing_settings where modelId in ({$modelsIds})
QUERY;
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('modelId', 'id', 'integer');

        $data = $this->getEntityManager()->createNativeQuery($leasingQuery, $rsm)->getArrayResult();
        $leasingModels = array_column($data, null,'id');

        $subscriptionQuery = <<<QUERY
            select model_id, min(price) as price from subscription_models where model_id in ({$modelsIds}) group by model_id
QUERY;
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('model_id', 'id', 'integer');
        $rsm->addScalarResult('price', 'price', 'integer');

        $data = $this->getEntityManager()->createNativeQuery($subscriptionQuery, $rsm)->getArrayResult();
        $subscriptionModels = array_column($data, null, 'id');

        $longDriveQuery = <<<QUERY
            select distinct model_id, min(price) as price from long_drive_models where model_id in ({$modelsIds}) group by model_id
QUERY;
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('model_id', 'id', 'integer');
        $rsm->addScalarResult('price', 'price', 'integer');

        $data = $this->getEntityManager()->createNativeQuery($longDriveQuery, $rsm)->getArrayResult();
        $longDriveModels = array_column($data, null, 'id');


        $result = [];
        foreach ($models as $model) {
            $modelId = $model->getId();
            $result[$modelId] = [
                'loan' => true,
                'leasing' => isset($leasingModels[$modelId]),
                'subscription' => isset($subscriptionModels[$modelId]),
                'longDrive' => isset($longDriveModels[$modelId]),
            ];
            if (isset($subscriptionModels[$modelId])) {
                $result[$modelId]['subscriptionPrice'] = $subscriptionModels[$modelId]['price'];
            }
            if (isset($longDriveModels[$modelId])) {
                $result[$modelId]['longDrivePrice'] = $longDriveModels[$modelId]['price'];
            }
        }

        return $result;
    }

    public function listCatalogForWebSite(ListModelsRequest $request): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.bodyType is not null');

        if ($request->search) {
            $qb->leftJoin('m.brand', 'b')
                ->where($qb->expr()->orX(
                    "CONCAT(m.name, ' ', b.name) LIKE :word",
                    "CONCAT(b.name, ' ', m.name) LIKE :word"
                ))
                ->setParameter('word', '%' . $request->search . '%', Types::STRING);
        }

        if ($request->withTestDrives) {
            $qb->leftJoin('m.equipments', 'e')
                ->leftJoin('e.cars', 'c', Join::WITH, 'c.status IN (1,2)')
                ->andWhere('c.id is not null');
        }

        if ($request->withFreeSchedule) {
            $qb->leftJoin('m.equipments', 'e')
                ->leftJoin('e.cars', 'c', Join::WITH, 'c.status IN (1,2) AND c.freeScheduleTime > NOW()')
                ->andWhere('c.id is not null');
        }

        if ($request->bodyTypes) {
            $qb->andWhere('m.bodyType in (:bodyType)')
                ->setParameter('bodyType', $request->bodyTypes);
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
            $total = $count->select('COUNT(m.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            $total = count($items);
        }

        return ['items' => $items, 'count' => $total];
    }

    /**
     * Формируем список фильтров по моделям для отдачи на веб
     *
     * @param ListModelsRequest $request
     *
     * @return array
     */
    public function listFiltersForWebSite(ListModelsRequest $request): array
    {
        return [
            'brand'    => $this->getBrandFiltersForModelCatalog($request),
            'model'    => $this->getModelFiltersForModelCatalog($request),
            'bodyType' => $this->getBodyTypesFiltersForModelCatalog($request),
        ];
    }

    /**
     * @param ListModelsRequest $request
     *
     * @return array
     */
    private function getBrandFiltersForModelCatalog(ListModelsRequest $request): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->from(Model::class, 'models')
            ->andWhere('models.bodyType is not null');

        // бренды
        $brandsQuery = (clone $qb)->select('distinct brand.id as id, count(models.id) as count')
            ->leftJoin('models.brand', 'brand');
        $brandsQuery->groupBy('brand.id');
        $totalBrands = array_column($brandsQuery->getQuery()->getArrayResult(), null, 'id');

        if ($request->bodyTypes) {
            $brandsQuery->andWhere('models.bodyType in (:bodyType)')->setParameter('bodyType', $request->bodyTypes);
        }

        if ($request->withTestDrives) {
            $brandsQuery->leftJoin('models.equipments', 'e')
                ->leftJoin('e.cars', 'c', Join::WITH, 'c.status IN (1,2)')
                ->andWhere('c.id is not null');
        }

        if ($request->withFreeSchedule) {
            $brandsQuery->leftJoin('models.equipments', 'e')
                ->leftJoin('e.cars', 'c', Join::WITH, 'c.status IN (1,2) AND c.freeScheduleTime > NOW()')
                ->andWhere('c.id is not null');
        }

        $filteredBrands = array_column($brandsQuery->getQuery()->getArrayResult(), null, 'id');

        $filteredBrands ??= $totalBrands;

        $brands = $this->getEntityManager()->createQueryBuilder()
            ->select('brand')
            ->from(Brand::class, 'brand')
            ->where('brand.id IN (:ids)')
            ->setParameter('ids', array_keys($totalBrands))
            ->getQuery()
            ->getResult();

        $brands = array_map(static function (Brand $brand) use ($filteredBrands, $request) {
            return [
                'id'     => $brand->getId(),
                'name'   => $brand->getName(),
                'photo'  => $brand->getLogo() ? $brand->getLogo()->getAbsolutePath() : null,
                'active' => isset($filteredBrands[$brand->getId()]),
                'count'  => isset($filteredBrands[$brand->getId()]) ? $filteredBrands[$brand->getId()]['count'] : 0,
                'selected' => in_array($brand->getId(), $request->brands, true),
            ];
        }, $brands);

        usort($brands, static fn($brandA, $brandB) => $brandB['active'] <=> $brandA['active']);

        return $brands;
    }

    /**
     * @param ListModelsRequest $request
     *
     * @return array
     */
    private function getBodyTypesFiltersForModelCatalog(ListModelsRequest $request): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->from(Model::class, 'models')
            ->andWhere('models.bodyType is not null');

        // типы кузовов
        $bodyTypesQuery = (clone $qb)->select('distinct models.bodyType as bodyType, count(models.id) as count')
            ->groupBy('models.bodyType');
        $totalBodyTypes = array_column($bodyTypesQuery->getQuery()->getArrayResult(), null, 'bodyType');

        if ($request->brands) {
            $bodyTypesQuery->andWhere('models.brand in (:brands)')->setParameter('brands', $request->brands);
        }

        if ($request->withTestDrives) {
            $bodyTypesQuery->leftJoin('models.equipments', 'e')
                ->leftJoin('e.cars', 'c', Join::WITH, 'c.status IN (1,2)')
                ->andWhere('c.id is not null');
        }

        if ($request->withFreeSchedule) {
            $bodyTypesQuery->leftJoin('models.equipments', 'e')
                ->leftJoin('e.cars', 'c', Join::WITH, 'c.status IN (1,2) AND c.freeScheduleTime > NOW()')
                ->andWhere('c.id is not null');
        }
        $filteredBodyTypes = array_column($bodyTypesQuery->getQuery()->getArrayResult(), null,'bodyType');


        $filteredBodyTypes ??= $totalBodyTypes;

        $bodyTypes = $this->dictionaryService->getByType('model.bodyType');
        $bodyTypesCatalog = $this->bodyTypeRepository->findBy([
            'bodyTypeId' => $totalBodyTypes,
        ]);
        $bodyTypesPhotos = [];
        foreach ($bodyTypesCatalog as $bodyTypePhoto) {
            $brandId = $bodyTypePhoto->getBrand() ? $bodyTypePhoto->getBrand()->getId() : null;
            if (count($request->brands) === 1 && ($brandId === $request->brands[0])) {
                $bodyTypesPhotos[$bodyTypePhoto->getBodyTypeId()] = $bodyTypePhoto->getPhoto();
            } elseif (!$brandId) {
                $bodyTypesPhotos[$bodyTypePhoto->getBodyTypeId()] ??= $bodyTypePhoto->getPhoto();
            }
        }

        $resultBodyTypes = [];
        foreach ($bodyTypes as $bodyTypeId=>$bodyTypeText) {
            if (!isset($totalBodyTypes[$bodyTypeId])) {
                continue;
            }

            $resultBodyTypes[] = [
                'id' => $bodyTypeId,
                'name' => $bodyTypeText,
                'photo' => isset($bodyTypesPhotos[$bodyTypeId]) ? $bodyTypesPhotos[$bodyTypeId]->getAbsolutePath() : null,
                'active' => isset($filteredBodyTypes[$bodyTypeId]),
                'count' => isset($filteredBodyTypes[$bodyTypeId]) ? $filteredBodyTypes[$bodyTypeId]['count'] : 0,
                'selected' => in_array($bodyTypeId, $request->bodyTypes, true),
            ];
        }

        usort($resultBodyTypes, static fn($bodyTypeA, $bodyTypeB) => $bodyTypeB['active'] <=> $bodyTypeA['active']);

        return $resultBodyTypes;
    }

    /**
     * @param ListModelsRequest $request
     *
     * @return array
     */
    private function getModelFiltersForModelCatalog(ListModelsRequest $request): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->from(Model::class, 'models')
            ->andWhere('models.bodyType is not null');

        // бренды
        $modelsQuery = (clone $qb)->select('distinct models.id');
        $totalModels = array_column($modelsQuery->getQuery()->getArrayResult(), 'id');

        if ($request->brands) {
            $modelsQuery->andWhere('models.brand in (:brands)')->setParameter('brands', $request->brands);
        }

        if ($request->bodyTypes) {
            $modelsQuery->andWhere('models.bodyType in (:bodyType)')->setParameter('bodyType', $request->bodyTypes);
        }

        if ($request->withFreeSchedule) {
            $modelsQuery->leftJoin('models.equipments', 'e')
                ->leftJoin('e.cars', 'c', Join::WITH, 'c.status IN (1,2) AND c.freeScheduleTime > NOW()')
                ->andWhere('c.id is not null');
        }

        $filteredModels = array_column($modelsQuery->getQuery()->getArrayResult(), 'id');

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
                'id'     => $model->getId(),
                'name'   => $model->getName(),
                'photo'  => $model->getSitePhoto() ? $model->getSitePhoto()->getAbsolutePath() : null,
                'active' => in_array($model->getId(), $filteredModels, true),
                'count'  => in_array($model->getId(), $filteredModels, true) ? 1 : 0,
                'selected' => in_array($model->getId(), $request->models, true),
            ];
        }, $models);

        usort($models, static fn($modelA, $modelB) => $modelB['active'] <=> $modelA['active']);

        return $models;
    }

    /**
     * Ищем модели в той же ценовой категории, что и текущая модель
     *
     * @param Model $model
     * @param float $diffPercent
     *
     * @return array
     */
    public function searchAnalogsForModel(Model $model, float $diffPercent): array
    {
        $modelData = $this->getStocksDataForModel([$model])[$model->getId()] ?? [];
        $carPrice = $model->getActiveCar() ? $model->getActiveCar()->getEquipment()->getPrice() : null;
        $minPrice = $modelData['stock_min_price'] ?? $carPrice;
        $maxPrice = $modelData['stock_max_price'] ?? $carPrice;

        if (!$minPrice || !$maxPrice) {
            return [];
        }

        $lowMinPrice = $minPrice * (1 - $diffPercent);
        $highMaxPrice = $maxPrice * (1 + $diffPercent);

        $query = <<<QUERY
            SELECT
                models.id
            FROM
                models
                left join (
                    select 
						cars.id as car_id,
                        cars.freeScheduleTime,
                        equipments.modelId,
                        equipments.price
                    from cars
                    left join equipments on equipments.id = cars.equipmentId
                    where cars.deletedAt is null and equipments.deletedAt is null and cars.status = 1
                    order by cars.freeScheduleTime
                ) as test_drives ON test_drives.modelId = models.id
                LEFT JOIN (
                    SELECT
                        count(dealer_cars.id) AS stocks_count,
                        min(dealer_cars.price) AS stock_min_price,
                        max(dealer_cars.price) AS stock_max_price,
                        dealer_equipments.modelId
                    FROM
                        dealer_cars
                        LEFT JOIN dealer_equipments ON dealer_equipments.id = dealer_cars.equipmentId
                    WHERE
                        dealer_cars.deletedAt IS NULL
                        AND dealer_equipments.deletedAt IS NULL
                        AND dealer_cars.`state` <> 3
                        AND dealer_cars.`price` > 0
                    GROUP BY 
                        dealer_equipments.modelId
                ) AS stocks ON stocks.modelId = models.id
            WHERE models.body_type is not null 
                AND COALESCE(stocks.stock_min_price, test_drives.price) is not null
                AND (
                    (COALESCE(stocks.stock_min_price, test_drives.price) >= {$lowMinPrice} AND COALESCE(stocks.stock_min_price, test_drives.price) <= {$highMaxPrice})
                    OR 
                    (COALESCE(stocks.stock_max_price, test_drives.price) >= {$lowMinPrice} AND COALESCE(stocks.stock_max_price, test_drives.price) <= {$highMaxPrice})
                )
            ;
QUERY;

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', 'integer');

        $ids = $this->getEntityManager()->createNativeQuery($query, $rsm)->getArrayResult();
        $ids = array_column($ids, 'id');
        $ids = array_filter($ids, static fn($id) => $id !== $model->getId());

        return $this->findBy(['id' => $ids]);
    }
}
