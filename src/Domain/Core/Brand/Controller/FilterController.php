<?php

namespace App\Domain\Core\Brand\Controller;

use CarlBundle\Repository\Brand\BrandRepository;
use Exception;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Контроллер списков брендов, необходимых для фильтрации в различных частях админки
 *
 * @deprecated надо сделать нормальные фильтры
 */
class FilterController extends AbstractController
{
    private BrandRepository $brandRepository;

    public function __construct(
        BrandRepository $brandRepository
    )
    {
        $this->brandRepository = $brandRepository;
    }

    /**
     * Вернёт все бренды с моделями по клиентским машинам
     *
     * @OA\Get(operationId="brand/models-with-approved-cars")
     *
     * @OA\Tag(name="System\Brands")
     *
     * @throws Exception
     */
    public function getBrandsWithModelsForClientCarsAction(): array
    {
        return $this->brandRepository->getBrandsWithModelsForApprovedClientCars();
    }

    /**
     *
     * Вернёт все бренды с моделями по существующим поездкам
     *
     * @OA\Get(operationId="brand/models-with-drives")
     *
     * @OA\Tag(name="System\Brands")
     *
     * @throws Exception
     */
    public function getBrandsWithModelsAndDrivesAction(): array
    {
        return $this->brandRepository->getBrandsWithModelsAndDrives();
    }
}
