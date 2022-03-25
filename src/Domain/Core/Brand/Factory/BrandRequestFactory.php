<?php

namespace App\Domain\Core\Brand\Factory;

use App\Domain\Core\Brand\Controller\Request\AdminBrandDataRequest;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\BrandCity;
use CarlBundle\Entity\BrandPhoto;
use CarlBundle\Entity\City;
use CarlBundle\Entity\Dealer;
use CarlBundle\Entity\Photo;
use CarlBundle\Exception\InvalidValueException;
use Doctrine\ORM\EntityManagerInterface;
use function is_array;

/**
 * Фабрика обработки запросов для работы с брендом
 */
class BrandRequestFactory
{
    private EntityManagerInterface $EntityManager;

    public function __construct(
        EntityManagerInterface $EntityManager
    )
    {
        $this->EntityManager = $EntityManager;
    }

    /**
     * Обрабатываем запрос на создание бренда
     *
     * @param AdminBrandDataRequest $Request
     * @return Brand
     * @throws InvalidValueException
     */
    public function create(AdminBrandDataRequest $Request): Brand
    {
        $Brand = new Brand();

        $this->update($Brand, $Request);

        return $Brand;
    }

    /**
     * Обрабатываем запрос на обновление бренда
     *
     * @param Brand $Brand
     * @param AdminBrandDataRequest $Request
     * @return Brand
     * @throws InvalidValueException
     */
    public function update(Brand $Brand, AdminBrandDataRequest $Request): Brand
    {
        $Brand->setName($Request->name)
            ->setDescription($Request->description)
            ->setPhoneSupport($Request->phoneSupport)
            ->setSite($Request->site)
            ->setFacebook($Request->facebook)
            ->setInstagram($Request->instagram)
            ->setVk($Request->vk);

        if (is_array($Request->dealers)) {
            $Dealers = $this->resolveDealers($Request->dealers);
            $Brand->setDealers($Dealers);
        }

        if (is_array($Request->photos)) {
            $Photos = $this->resolvePhotos($Request->photos);
            $Brand->setBrandPhotos($Photos);
        }

        if (is_array($Request->cities)) {
            $Cities = $this->resolveCities($Request->cities);
            $Brand->setCities($Cities);
        }

        return $Brand;
    }

    /**
     * @codeCoverageIgnore
     *
     * Обрабатываем пришедший список связанных с брендом дилеров
     *
     * @param array $dealers
     *
     * @return array
     */
    public function resolveDealers(array $dealers): array
    {
        if ($dealers && is_array($dealers[0])) {
            $dealers = array_column($dealers, 'id');
        }

        return $this->EntityManager->getRepository(Dealer::class)->findBy([
            'id' => $dealers,
        ]);
    }

    /**
     * Обрабатываем пришедший список связанных с брендом фотографий
     *
     * @param array $photos
     * @return array
     * @throws InvalidValueException
     */
    public function resolvePhotos(array $photos): array
    {
        if (!$photos) {
            return [];
        }

        if (!is_array($photos[0])) {
            throw new InvalidValueException('Неверный формат фотографий бренда');
        }

        $photosIds = array_column($photos, 'id');
        $Photos = $this->EntityManager->getRepository(Photo::class)->findBy([
            'id' => $photosIds
        ]);

        // реформируем его во что-то более удобное
        $IndexedPhotos = [];
        foreach ($Photos as $Photo) {
            $IndexedPhotos[$Photo->getId()] = $Photo;
        }

        $BrandPhotos = [];
        foreach($photos as $photoData) {
            if (!isset($photoData['id'], $IndexedPhotos[$photoData['id']])) {
                continue;
            }
            $BrandPhoto = new BrandPhoto();
            $BrandPhoto
                ->setPhoto($IndexedPhotos[$photoData['id']])
                ->setType($photoData['type'] ?? null);

            if (isset($photoData['status'])) {
                $BrandPhoto->setStatus($photoData['status']);
            }
            if (isset($photoData['title'])) {
                $BrandPhoto->setTitle(trim($photoData['title']));
            }

            $BrandPhotos[] = $BrandPhoto;
        }

        return $BrandPhotos;
    }

    /**
     * @codeCoverageIgnore
     *
     * Обрабатываем пришедший список связанных с брендом городов
     *
     * @param array $cities
     * @return array
     */
    public function resolveCities(array $cities): array
    {
        $BrandCities = [];
        foreach ($cities as $city){
            if (!is_array($city)) {
                continue;
            }
            $City = $this->EntityManager->getRepository(City::class)->find($city['id']);
            $BrandCity = new BrandCity();
            $BrandCity
                ->setCity($City)
                ->setState($city['state']);
            $BrandCities [] = $BrandCity;
        }

        return $BrandCities;
    }
}
