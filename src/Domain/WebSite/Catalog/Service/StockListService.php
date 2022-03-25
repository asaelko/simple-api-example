<?php

namespace App\Domain\WebSite\Catalog\Service;

use App\Domain\Core\Model\Repository\ModelRepository;
use App\Domain\WebSite\Catalog\Request\ListStockRequest;
use App\Domain\WebSite\Catalog\Response\StockCarWithDriveDataResponse;
use App\Domain\WebSite\Catalog\Response\StockResponse;
use CarlBundle\Entity\CarPhotoDictionary;
use CarlBundle\Helpers\FiltersHelper;
use CarlBundle\Helpers\SortHelper;
use DealerBundle\Entity\Car;
use DealerBundle\ServiceRepository\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StockListService
{
    private EntityManagerInterface $entityManager;
    private StockRepository $stockRepository;
    private ModelRepository $modelRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        StockRepository $stockRepository,
        ModelRepository $modelRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->stockRepository = $stockRepository;
        $this->modelRepository = $modelRepository;
    }

    /**
     * Отдает список машин из стока
     *
     * @param ListStockRequest $request
     *
     * @return StockResponse
     */
    public function list(ListStockRequest $request): StockResponse
    {
        $availableFilters = $this->stockRepository->getAvailableFilters();
        $availableSortFields = $this->stockRepository->getAvailableSortFields();

        $sortFields = SortHelper::makeSortFields($request->sort, $availableSortFields);
        $filters = FiltersHelper::reformatFilters((array) $request, $availableFilters);

        $data = $this->stockRepository->searchStockCars($request->limit, $request->offset, $filters, $sortFields);
        $filterValues = $this->stockRepository->getFilterValues($filters);

        $dealerCars = $data['items'];
        $this->addCarPhotoDictionaryInDealerCar($dealerCars);

        $models = array_map(static fn(Car $car) => $car->getEquipment()->getModel(), $dealerCars);
        $tagData = $this->modelRepository->getTagsForModels($models);

        return new StockResponse($dealerCars, $data['count'], $filterValues, $tagData);
    }

    /**
     * Отдает конкретную машину из стока
     *
     * @param int $stockId
     *
     * @return StockCarWithDriveDataResponse
     */
    public function show(int $stockId): StockCarWithDriveDataResponse
    {
        /** @var Car $car */
        $car = $this->stockRepository->find($stockId);

        if (!$car) {
            throw new NotFoundHttpException('Машина не найдена');
        }

        $this->addCarPhotoDictionaryInDealerCar([$car]);
        $tagData = $this->modelRepository->getTagsForModels([$car->getEquipment()->getModel()]);

        return new StockCarWithDriveDataResponse($car, $tagData);
    }

    /**
     * Устанавливает carPhotoDictionary в DealerCar
     *
     * @todo вынести в другой сервис
     *
     * @param Car[] $dealerCars
     * @return Car[]
     */
    public function addCarPhotoDictionaryInDealerCar(array $dealerCars = []): array
    {
        if (!$dealerCars) {
            return [];
        }

        $equipments = array_map(static function (Car $dealerCar) {
            return $dealerCar->getEquipment()->getId();
        }, $dealerCars);

        $equipments = array_unique($equipments);

        // ищем все equipment-ы переданных Дилерских машин и дофильтровываем их для каждой машины
        $CarPhotoDictionaryRepository = $this->entityManager->getRepository(CarPhotoDictionary::class);
        $carPhotoDictionaries = $CarPhotoDictionaryRepository->findBy(['equipment' => $equipments]);

        $carPhotoDictionaries = new ArrayCollection($carPhotoDictionaries);

        foreach ($dealerCars as &$dealerCar) {
            if (!$dealerCar->getEquipment()) {
                // если для дилерской машины комплектация не указана
                continue;
            }
            $criteria = Criteria::create();
            $criteria
                ->andWhere(
                    Criteria::expr()->andX(
                        Criteria::expr()->eq('brand', $dealerCar->getEquipment()->getModel() ? $dealerCar->getEquipment()->getModel()->getBrand() : null),
                        Criteria::expr()->eq('model', $dealerCar->getEquipment()->getModel()),
                        Criteria::expr()->eq('equipment', $dealerCar->getEquipment()),
                        Criteria::expr()->eq('colorHex', $dealerCar->getCarColor() ? $dealerCar->getCarColor()->getHex() : null),
                        Criteria::expr()->neq('colorHex', null),
                        Criteria::expr()->eq('year', $dealerCar->getPTSyear())
                    )
                );
            $matchingCarPhotoDictionaries = $carPhotoDictionaries->matching($criteria);
            $dealerCar->setCarPhotoDictionaries($matchingCarPhotoDictionaries->toArray());
        }

        return $dealerCars;
    }


}