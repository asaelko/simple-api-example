<?php

namespace App\Domain\WebSite\Catalog\Response;

use CarlBundle\Entity\Brand;
use CarlBundle\Entity\Dealer;
use CarlBundle\Entity\DealerCarColor;
use CarlBundle\Entity\Model\Model;
use DealerBundle\Entity\Car;
use DealerBundle\Entity\Equipment;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;

class StockResponse
{
    /**
     * @var array|StockCarResponse[]
     *
     * @OA\Property(type="array", @OA\Items(ref=@DocModel(type=StockCarResponse::class)))
     */
    public array $items;

    /**
     * @var int
     */
    public int $count;

    /**
     * @var array
     *
     * @OA\Property(type="array", @OA\Items(type="mixed"))
     */
    public array $filters;

    public function __construct(array $stockCars, int $count, array $filters, array $tagData = [])
    {
        $this->items = array_map(static fn(Car $car) => new StockCarResponse($car, $tagData), $stockCars);

        $this->count = $count;

        array_walk($filters, static function (&$filter, $key) {
            switch (true) {
                case $key === 'brandId':
                    $filter['values'] = array_map(static fn(Brand $brand) => [
                        'id'    => $brand->getId(),
                        'name'  => $brand->getName(),
                        'photo' => $brand->getLogo() ? $brand->getLogo()->getAbsolutePath() : null,
                        'count' => $filter['counts'][$brand->getId()]['count'] ?? 0,
                    ], $filter['values']);
                    break;
                case $key === 'modelId':
                    $filter['values'] = array_map(static fn(Model $model) => [
                        'id'    => $model->getId(),
                        'name'  => $model->getName(),
                        'photo' => $model->getSitePhoto() ? $model->getSitePhoto()->getAbsolutePath() : null,
                        'count' => $filter['counts'][$model->getId()]['count'] ?? 0,
                    ], $filter['values']);
                    break;
                case $key === 'equipmentId':
                    $filter['values'] = array_map(static fn(Equipment $equipment) => [
                        'id'    => $equipment->getId(),
                        'name'  => $equipment->getName(),
                        'count' => $filter['counts'][$equipment->getId()]['count'] ?? 0,
                    ], $filter['values']);
                    break;
                case $key === 'colorId':
                    $filter['values'] = array_map(static fn(DealerCarColor $color) => [
                        'id'    => $color->getId(),
                        'name'  => $color->getName(),
                        'hex'   => $color->getHex(),
                        'count' => $filter['counts'][$color->getId()]['count'] ?? 0,
                    ], $filter['values']);
                    break;
                case $key === 'dealerId':
                    $filter['values'] = array_map(static fn(Dealer $dealer) => [
                        'id'    => $dealer->getId(),
                        'name'  => $dealer->getName(),
                        'count' => $filter['counts'][$dealer->getId()]['count'] ?? 0,
                    ], $filter['values']);
                    break;
                case $filter['type'] === 'interval':
                    $filter['availableValues'] = $filter['counts'];
                    break;
                case $filter['type'] === 'list':
                    $filter['values'] = array_map(static fn($data) =>
                        $data + ['count' => $filter['counts'][$data['id']]['count'] ?? 0],
                        $filter['values']
                    );
                    break;
            }

            if ($filter['type'] === 'list') {
                usort($filter['values'], static fn($valueA, $valueB) => $valueB['count'] <=> $valueA['count']);
            }

            unset($filter['counts']);
        });

        $this->filters = $filters;
    }
}
