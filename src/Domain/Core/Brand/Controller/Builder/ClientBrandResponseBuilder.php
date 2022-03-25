<?php

namespace App\Domain\Core\Brand\Controller\Builder;

use App\Domain\Core\Brand\Controller\Response\Client\ClientBrandResponse;
use App\Domain\Core\Model\Repository\ModelRepository;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\Model\ModelPhoto;
use CarlBundle\Repository\Model\BodyTypeCatalogRepository;
use CarlBundle\Service\DictionaryService;
use Doctrine\ORM\EntityManagerInterface;

class ClientBrandResponseBuilder
{
    private const BASE_BODY_TYPE_BRAND = -1;

    private ModelRepository $modelRepository;
    private EntityManagerInterface $entityManager;

    private array $bodyTypes;

    public function __construct(
        DictionaryService $dictionaryService,
        BodyTypeCatalogRepository $bodyTypeRepository,
        ModelRepository $modelRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->modelRepository = $modelRepository;

        $bodyTypes = $dictionaryService->getByType('model.bodyType');
        $bodyTypesCatalog = $bodyTypeRepository->findAll();

        foreach ($bodyTypesCatalog as $bodyTypePhoto) {
            $bodyTypeId = $bodyTypePhoto->getBodyTypeId();
            $brandId = $bodyTypePhoto->getBrand()? $bodyTypePhoto->getBrand()->getId() : self::BASE_BODY_TYPE_BRAND;

            $this->bodyTypes[$bodyTypeId] ??= [];
            $this->bodyTypes[$bodyTypeId][$brandId] = [
                'id'    => $bodyTypeId,
                'text'  => $bodyTypes[$bodyTypeId],
                'photo' => $bodyTypePhoto->getPhoto(),
            ];
        }
        foreach ($bodyTypes as $bodyTypeId=>$bodyTypeText) {
            $this->bodyTypes[$bodyTypeId][self::BASE_BODY_TYPE_BRAND] ??= ['id' => $bodyTypeId, 'text' => $bodyTypeText];
        }
        $this->entityManager = $entityManager;
    }

    /**
     * Собирает данные для одного бренда
     *
     * @param Brand $brand
     * @return ClientBrandResponse
     */
    public function buildForBrand(Brand $brand): ClientBrandResponse
    {
        $models = $this->fetchModels([$brand]);

        $brandModels = $models[$brand->getId()] ?? [];
        $brandBodyTypes = $this->fetchBodyTypesByBrand($brand->getId(), array_column($brandModels, 'body_type'));

        return new ClientBrandResponse(
            $brand,
            $brandBodyTypes,
            $brandModels
        );
    }

    /**
     * Собирает данные для нескольких брендов
     *
     * @param array|Brand[] $brands
     * @return array
     */
    public function build(array $brands): array
    {
        if (!$brands) {
            return [];
        }

        $models = $this->fetchModels($brands);

        $result = [];
        foreach ($brands as $brand) {
            $brandModels = $models[$brand->getId()] ?? [];
            $brandBodyTypes = $this->fetchBodyTypesByBrand($brand->getId(), array_column($brandModels, 'body_type'));

            if (!$brandBodyTypes || !$brandModels) {
                continue;
            }

            $result[] = new ClientBrandResponse(
                $brand,
                $brandBodyTypes,
                $brandModels
            );
        }

        return $result;
    }

    /**
     * Достает модели с фото в нужном формате
     *
     * @param array $brands
     * @return array
     */
    private function fetchModels(array $brands): array
    {
        $models = $this->modelRepository->getRichModelsDataForBrands(
            array_map(static fn(Brand $brand) => $brand->getId(), $brands)
        );
        $modelsIds = array_map(
            static fn(array $modelData) => $modelData['id'], $models
        );

        /** @var ModelPhoto[] $modelPhotos */
        $modelPhotos = $this->entityManager->getRepository(ModelPhoto::class)->findBy([
            'model' => $modelsIds,
            'type' => [ModelPhoto::APP_PHOTO_TYPE]
        ]);

        $reIndexedPhotos = [];
        foreach ($modelPhotos as $modelPhoto) {
            $reIndexedPhotos[$modelPhoto->getModel()->getId()] = $modelPhoto->getPhoto();
        }

        $brandedModels = [];
        foreach ($models as $modelData) {
            $photo = $reIndexedPhotos[$modelData['id']] ?? null;
            if (!$photo) {
                continue;
            }

            $modelData['photo'] = $photo;
            $brandedModels[$modelData['brand_id']] ??= [];
            $brandedModels[$modelData['brand_id']][] = $modelData;
        }

        return $brandedModels;
    }

    /**
     * Отдает типы кузовов, их фото, и упаковывает в нужном формате
     *
     * @param int $brandId
     * @param array $bodyTypesIds
     *
     * @return array
     */
    private function fetchBodyTypesByBrand(int $brandId, array $bodyTypesIds): array
    {
        $bodyTypes = array_intersect_key($this->bodyTypes, array_flip($bodyTypesIds));

        $result = [];
        foreach ($bodyTypes as $id => $bodyTypeData) {
            $data = $bodyTypeData[$brandId] ?? $bodyTypeData[self::BASE_BODY_TYPE_BRAND] ?? null;
            if (!$data) {
                continue;
            }
            $result[$id] = $data;
        }

        return $result;
    }
}
