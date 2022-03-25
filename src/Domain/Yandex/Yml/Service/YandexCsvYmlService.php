<?php


namespace App\Domain\Yandex\Yml\Service;


use CarlBundle\Entity\Brand;
use CarlBundle\Entity\CarPhotoDictionary;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Entity\Schedule;
use CarlBundle\Service\DictionaryService;
use CarlBundle\Service\DriveRateService;
use DealerBundle\Entity\Car;
use DealerBundle\Entity\Equipment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use CarlBundle\Entity\Car as TestDriveCar;

class YandexCsvYmlService
{
    private EntityManagerInterface $entityManager;

    private DictionaryService $dictionaryService;

    private DriveRateService $driveRateService;

    public function __construct(
        EntityManagerInterface $entityManager,
        DictionaryService $dictionaryService,
        DriveRateService $driveRateService
    )
    {
        $this->entityManager = $entityManager;
        $this->dictionaryService = $dictionaryService;
        $this->driveRateService = $driveRateService;
    }

    public function generateYmlTestDrive()
    {
        try {
            $carsArray = $this->entityManager->getRepository(TestDriveCar::class)->getCarsByAvailability(true);

            $xml = new \SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><yml_catalog></yml_catalog>");
            $xml->addAttribute('date', (new \DateTime())->format('Y-m-d H:i:s'));
            $shop = $xml->addChild('shop');
            $shop->addChild('name', 'CARL');
            $shop->addChild('company', 'ООО КАРЛ РУС');
            $shop->addChild('url', 'https://carl-drive.ru');
            $currencies = $shop->addChild('currencies');
            $currency = $currencies->addChild('currency');
            $currency->addAttribute('id', 'RUR');
            $currency->addAttribute('rate', '1');

            $cars = $xml->addChild('cars');
            $bodyTypeDictionary = $this->dictionaryService->getByType('model.bodyType');

            foreach ($carsArray as $carEntity) {
                assert($carEntity instanceof TestDriveCar);

                $price = $this->driveRateService->getDriveRateForUser(null, $carEntity);

                if ($carEntity->getPhotoForCatalog()) {
                    $photo = "https://cdn.carl-drive.ru/uploads/{$carEntity->getPhotoForCatalog()->getAbsolutePath()}/original.png";
                } else {
                    continue;
                }

                $car = $cars->addChild('car');
                $car->addChild('mark_id', $carEntity->getEquipment()->getModel()->getBrand()->getName());
                $car->addChild('folder_id', $carEntity->getEquipment()->getModel()->getName());
                $car->addChild('modification_id', $carEntity->getEquipment()->getName() ?? '');
                $car->addChild('url', "https://carl-drive.ru/car/{$carEntity->getId()}");
                $car->addChild('images', $photo);
                $car->addChild('body_type', $bodyTypeDictionary[$carEntity->getEquipment()->getModel()->getBodyType()] ?? '');
                $car->addChild('color', $carEntity->getColor() ? $carEntity->getColor()->getName() : '');
                $car->addChild('availability', 'в наличии');
                $car->addChild('custom', 'растаможен');
                $car->addChild('year', '');
                $car->addChild('price', $price->getPrice() ?? 0);
                $car->addChild('currency', 'RUR');
                $car->addChild('vin', '');
                $car->addChild('unique_id', $carEntity->getId());
            }

            return $xml;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function generateCsvDrive(): string
    {
        try {
            $carsArray = $this->entityManager->getRepository(TestDriveCar::class)->getCarsByAvailability(true);

            $csv = "ID,ID2,URL,image,Title,Description,Price,Currency,Old Price" . PHP_EOL;

            foreach ($carsArray as $car) {
                assert($car instanceof TestDriveCar);

                $price = $this->driveRateService->getDriveRateForUser(null, $car);

                if ($car->getPhotoForCatalog()) {
                    $photo = "https://cdn.carl-drive.ru/uploads/{$car->getPhotoForCatalog()->getAbsolutePath()}/original.png";
                } else {
                    continue;
                }

                $csv .= "{$car->getId()},,https://carl-drive.ru/{$car->getId()}," .
                    "{$photo},,{$price->getPrice()},RUB,," . PHP_EOL;
            }

            return $csv;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function generateOffersYml()
    {
        try {

            $brands = $this->entityManager->getRepository(Brand::class)->findAll();

            $xml = new \SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><catalog></catalog>");
            $bodyTypeDictionary = $this->dictionaryService->getByType('model.bodyType');
            foreach ($brands as $brand) {
                assert($brand instanceof Brand);

                $mark = $xml->addChild('mark');
                $mark->addAttribute('name', $brand->getName());
                $mark->addAttribute('id', $brand->getId());
                $mark->addChild('code', $brand->getName());

                $models = $this->entityManager->getRepository(Model::class)->findBy(
                    [
                        'brand' => $brand,
                    ]
                );
                foreach ($models as $modelEntity) {
                    assert($modelEntity instanceof Model);
                    $folder = $mark->addChild('folder', urlencode($modelEntity->getName()));
                    $folder->addChild('model', urlencode($modelEntity->getName()));
                    $generation = $folder->addChild('generation');
                    $generation->addAttribute('id', '');

                    $equipments = $this->entityManager->getRepository(Equipment::class)->getEquipments(
                        new ArrayCollection(),
                        [$modelEntity],
                    );

                    foreach ($equipments['items'] as $equipmentEntity) {
                        assert($equipmentEntity instanceof Equipment);

                        $modification = $folder->addChild('modification');
                        $modification->addAttribute('name', $equipmentEntity->getName() ?? '');
                        $modification->addAttribute('id', $modelEntity->getId());
                        $modification->addChild('mark_id', $modelEntity->getName());
                        $modification->addChild('folder_id', $modelEntity->getId());
                        $modification->addChild('modification_id', $equipmentEntity->getName());
                        $modification->addChild('configuration_id', $equipmentEntity->getId());
                        $modification->addChild('tech_param_id');
                        $modification->addChild('bodyType', $bodyTypeDictionary[$equipmentEntity->getModel()->getBodyType()] ?? '');
                        $modification->addChild('years', $equipmentEntity->getCars()->isEmpty() ? '' : $equipmentEntity->getCars()->first()->getPTSyear());
                        $modification->addChild('complectations');
                    }
                }
            }
            return $xml;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}