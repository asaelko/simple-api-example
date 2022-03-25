<?php

namespace App\Domain\Core\Geo\Service;

use App\Domain\Core\Geo\Helper\GeoHelper;
use CarlBundle\Entity\City;
use CarlBundle\Exception\RestException;
use CarlBundle\Service\Geo\LatLng;
use Dadata\DadataClient;
use GeoIp2\Database\Reader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GeoDataService
{
    public const DEFAULT_CITY_ID = 0;
    public const MOSCOW_ID     = 1;
    public const PETERSBURG_ID = 2;

    private DadataClient $dadataClient;
    private GeoHelper $geoHelper;
    private Reader $geoIpReader;

    public function __construct(
        ParameterBagInterface $parameterBag,
        GeoHelper $geoHelper,
        Reader $geoIpReader
    )
    {
        $this->dadataClient = new DadataClient($parameterBag->get('dadata.token'), $parameterBag->get('dadata.secret'));
        $this->geoHelper = $geoHelper;
        $this->geoIpReader = $geoIpReader;
    }

    /**
     * Генерируем дефолтный город для клиента
     *
     * @return City
     */
    public function getDefaultCity(): City
    {
        $city = new City();
        return $city->setId(self::DEFAULT_CITY_ID)
            ->setName('Другой город');
    }

    /**
     * Получаем данные по городу по IP
     *
     * @param string $ip
     *
     * @return \GeoIp2\Model\City|null
     */
    public function getIpCityData(string $ip): ?\GeoIp2\Model\City
    {
        try {
            return $this->geoIpReader->city($ip);
        } catch (\GeoIp2\Exception\AddressNotFoundException $ex) {
            return null;
        } catch (\MaxMind\Db\Reader\InvalidDatabaseException $ex) {
            return null;
        }
    }

    /**
     * @param City $city
     * @param float $lat
     * @param float $lon
     * @return string
     * @throws RestException
     */
    public function getAddressByGeoData(City $city, float $lat, float $lon): string
    {
        if (!$this->geoHelper::isPointInPolygon(new LatLng($lat, $lon), $city->getPolyline())) {
            throw new RestException('Адрес выходит за пределы разрешенной зоны бронирования', 421);
        }

        $address = $this->dadataClient->geolocate("address", $lat, $lon);

        $clearAddress = !empty($address) ? rtrim($address[0]['data']['street_with_type'] . ' ' . ($address[0]['data']['house_type_full'] ?? null) . ' ' . ($address[0]['data']['house'] ?? null)) : null;

        if ($clearAddress === null) {
            throw new NotFoundHttpException('Адрес не найден');
        }

        if (!$clearAddress) {
            $clearAddress = $address[0]['value'];
        }

        return $clearAddress;
    }

    /**
     * @param City $city
     * @param string $address
     * @return array|null
     * @throws RestException
     */
    public function getGeoByAddress(City $city, string $address): ?array
    {
        $geo = $this->dadataClient->clean("address", $address);
        if (!$this->geoHelper::isPointInPolygon(new LatLng($geo['geo_lat'], $geo['geo_lon']), $city->getPolyline())) {
            throw new RestException('Адрес выходит за пределы зоны бронирования', 421);
        }

        return !empty($geo) ? ['lat' => $geo['geo_lat'], 'lon' => $geo['geo_lon']] : null;
    }
}
