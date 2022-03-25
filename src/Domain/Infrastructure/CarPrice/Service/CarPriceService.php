<?php
namespace App\Domain\Infrastructure\CarPrice\Service;

use App\Entity\CarPriceBrands;
use App\Entity\CarPriceCar;
use App\Entity\CarPriceModels;
use App\Entity\CarPriceOffice;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CarPriceService
{
    public const BASE_URL = "https://api.carprice.auction";

    public const CAR_PRICE_MOSCOW_ID = 671;

    private string $key;

    private EntityManagerInterface $entityManager;

    private LoggerInterface $carPriceLogger;

    public function __construct(
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        LoggerInterface $carPriceLogger
    )
    {
        $this->key = $parameterBag->get('carprice.key');
        $this->entityManager = $entityManager;
        $this->carPriceLogger = $carPriceLogger;
    }

    /**
     * Вернет список доступных лет для моделей данного бренда
     * @param int $brandId
     * @return array
     */
    public function getBrandYears(int $brandId): array
    {
        try {
            $client = new Client();
            $request = $client->get(
                self::BASE_URL . '/client/evaluate-form/years',
                [
                    'query' => [
                        "brand_id" => $brandId
                    ],
                ],
            );

            $result = $request->getBody()->getContents();
            $data = json_decode($result, true);

            if ($data['success']) {
                return $data['data'];
            }
            $this->carPriceLogger->error($result);
            return [];
        } catch (\Exception $e) {
            $this->carPriceLogger->error($e->getMessage());
            return [];
        }
    }

    /**
     * Вернет список брендов которые есть у carPrice
     * @return array
     */
    public function getBrands(): array
    {
        try {
            $client = new Client();
            $request = $client->get(self::BASE_URL . '/client/evaluate-form/brands');

            $result = $request->getBody()->getContents();
            $data = json_decode($result, true);

            if ($data['success']) {
                return $data['data'];
            }
            $this->carPriceLogger->error($result);
            return [];
        } catch (\Exception $e) {
            $this->carPriceLogger->error($e->getMessage());
            return [];
        }
    }

    /**
     * Вернет Список моделей у CarPrice по бренду и году выпуска
     * @param int $brandId
     * @param int $year
     * @return array
     */
    public function getModels(int $brandId, int $year): array
    {
        try {
            $client = new Client();
            $request = $client->get(
                self::BASE_URL . '/client/evaluate-form/models',
                [
                    'query' => [
                        "brand_id" => $brandId,
                        "year" => $year
                    ],
                ]
            );

            $result = $request->getBody()->getContents();
            $data = json_decode($result, true);
            if ($data['success']) {
                return $data['data'];
            }
            $this->carPriceLogger->error($result);
            return [];
        } catch (\Exception $e) {
            $this->carPriceLogger->error($e->getMessage());
            return [];
        }
    }

    /**
     * Вернет список модификаций для бренда и модели
     * @param int $brandId
     * @param int $modelId
     * @return array|mixed
     */
    public function getModification(int $brandId, int $modelId): array
    {
        try {
            $client = new Client();
            $request = $client->get(
                self::BASE_URL . '/client/evaluate-form/modifications',
                [
                    'query' => [
                        "brand_id" => $brandId,
                        "model_id" => $modelId
                    ],
                ]
            );
            $result = $request->getBody()->getContents();
            $data = json_decode($result, true);
            if ($data['success']) {
                return $data['data'];
            }
            $this->carPriceLogger->error($result);
            return [];
        } catch (\Exception $e) {
            $this->carPriceLogger->error($e->getMessage());
            return [];
        }
    }

    /**
     * Создает запрос на добавление машины в кп и первичной ОЧЕНЬ грубой оценки стоимости
     * @param int $brandId
     * @param int $year
     * @param int $modelId
     * @return array|mixed
     */
    public function createCarInCarPrice(int $brandId, int $year, int $modelId): array
    {
        try {
            $client = new Client();
            $request = $client->post(
                self::BASE_URL . '/client/api/v1.0.0/order/car/create',
                [
                    'query' => [
                        "api_token" => $this->key,
                    ],
                    'form_params' => [
                        'type_id' => 0,
                        'brand_id' => $brandId,
                        'year' => $year,
                        'model_id' => $modelId,
                        'city_id' => self::CAR_PRICE_MOSCOW_ID
                    ]
                ]
            );
            $result = $request->getBody()->getContents();
            $data = json_decode($result, true);
            if ($data['success']) {
                return $data['car'];
            }
            $this->carPriceLogger->error($result);
            return [];
        } catch (\Exception|RequestException $e) {
            $this->carPriceLogger->error($e->getMessage());
            return [];
        }
    }

    /**
     * Вернет мап по модели бренду
     * @param string $modelName
     * @param string $brandName
     * @return array|null
     */
    public function mapModelAndBrand(string $modelName, string $brandName): ?array
    {
        $brand = $this->entityManager->getRepository(CarPriceBrands::class)->mapCarPriceModel($brandName);

        if (empty($brand)) {
            return null;
        }

        $brand = array_shift($brand);

        $model = $this->entityManager->getRepository(CarPriceModels::class)->mapModel($brand, $modelName);

        if (empty($model)) {
            return null;
        }

        $model = array_shift($model);

        return ['model' => $model, 'brand' => $brand];
    }

    /**
     * @param CarPriceCar $car
     * @param DateTime $date
     * @param CarPriceOffice $office
     * @return array
     */
    public function createOrder(CarPriceCar $car, DateTime $date, CarPriceOffice $office): array
    {
        try {
            $client = new Client();
            $request = $client->post(
                self::BASE_URL . '/client/api/v1.0.0/order/create',
                [
                    'query' => [
                        "api_token" => $this->key,
                    ],
                    'form_params' => [
                        'phone' => $car->getClientCar()->getClient()->getPhone(),
                        'date' => $date->format('Y-m-d'),
                        'time' => $date->format('H:i'),
                        'first_name' => $car->getClientCar()->getClient()->getFirstName() ?? '-',
                        'last_name' => $car->getClientCar()->getClient()->getSecondName() ?? '-',
                        'car_code' => $car->getCarPriceId(),
                        'city_id' => self::CAR_PRICE_MOSCOW_ID,
                        'branch_id' => $office->getCode()
                    ]
                ]
            );
            $result = $request->getBody()->getContents();
            $data = json_decode($result, true);
            if ($data['success']) {
                return $data['order'];
            }
            $this->carPriceLogger->error($result);
            return [];
        } catch (\Exception|RequestException $e) {
            $this->carPriceLogger->error($e->getMessage());
            return [];
        }
    }

    /**
     * Грузит список всех офисов по мск
     * @return array
     */
    public function loadOffices(): array
    {
        try {
            $client = new Client();
            $request = $client->get(
                self::BASE_URL . '/client/api/v1.0.0/branches',
                [
                    'query' => [
                        "city_id" => self::CAR_PRICE_MOSCOW_ID,
                        "api_token" => $this->key,
                    ],
                ]
            );
            $result = $request->getBody()->getContents();
            $data = json_decode($result, true);
            if ($data['success']) {
                return $data['branches'];
            }
            $this->carPriceLogger->error($result);
            return [];
        } catch (\Exception|RequestException $e) {
            $this->carPriceLogger->error($e->getMessage());
            return [];
        }
    }
}