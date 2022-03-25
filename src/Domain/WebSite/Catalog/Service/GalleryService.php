<?php

namespace App\Domain\WebSite\Catalog\Service;

use App\Entity\Equipment\EquipmentMedia;
use App\Repository\Equipment\EquipmentMediaRepository;
use CarlBundle\Entity\Model\Model;

/**
 * Получение галереи медиа для веб-сайта по модели
 */
class GalleryService
{
    private EquipmentMediaRepository $repository;

    public function __construct(
        EquipmentMediaRepository $repository
    )
    {
        $this->repository = $repository;
    }

    /**
     * @param Model $model
     *
     * @return array
     */
    public function getGallery(Model $model): array
    {
        $catalog = [];
        $media = $this->repository->getMediaForModel($model);

        array_walk($media, static function(EquipmentMedia $equipmentMedia) use (&$catalog) {
           $catalog[$equipmentMedia->getCategory()] ??= [];
           $catalog[$equipmentMedia->getCategory()][$equipmentMedia->getEquipment()->getName()] ??= [];
           $catalog[$equipmentMedia->getCategory()][$equipmentMedia->getEquipment()->getName()][] = $equipmentMedia->getMedia();
        });

        return $catalog;
    }

    /**
     * @return array
     */
    public function getGalleryCategories(): array
    {
        return array_column($this->repository->getCategories(), 'category');
    }
}
