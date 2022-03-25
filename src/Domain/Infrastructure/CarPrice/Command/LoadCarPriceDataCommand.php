<?php
namespace App\Domain\Infrastructure\CarPrice\Command;

use App\Domain\Infrastructure\CarPrice\Service\CarPriceService;
use App\Entity\CarPriceBrands;
use App\Entity\CarPriceModels;
use App\Entity\CarPriceModifications;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadCarPriceDataCommand extends Command
{
    protected static $defaultName = 'car-price:load';

    protected function configure()
    {
        $this
            ->setDescription('Load data from car price data api')
            ->setHelp('Load data from car price data api');
    }

    private EntityManagerInterface $entityManager;
    private CarPriceService $carPriceService;
    private LoggerInterface $logger;

    public function __construct(
        string $name = null,
        EntityManagerInterface $entityManager,
        CarPriceService $carPriceService,
        LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->carPriceService = $carPriceService;
        parent::__construct($name);
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $brandsSection = $output->section();
        $modelsSection = $output->section();

        try {
            $brands = $this->carPriceService->getBrands();
            if (empty($brands)) {
                return 1;
            }
            $progressBar = new ProgressBar($brandsSection);
            $progressBar->start(count($brands));

            $brandsCache = [];
            $records = $this->entityManager->getRepository(CarPriceBrands::class)->findBy(
                ['brandId' => array_column($brands, null, 'value')]
            );
            array_walk(
                $records,
                static function(CarPriceBrands $value) use (&$brandsCache) {
                    $brandsCache[$value->getBrandId()] = $value;
                }
            );

            $modelsProgressBar = new ProgressBar($modelsSection);
            foreach ($brands as $brand) {
                $this->loadBrand($modelsProgressBar, $brand, $brandsCache);
                $progressBar->advance();
            }
            $progressBar->finish();
            return 0;
        } catch (Exception $e) {
            $this->logger->error($e);
            return 1;
        }
    }

    private function loadBrand(ProgressBar $progressBar, array $brandData, array $brandCache)
    {
        $brandId = $brandData['value'];
        $brandRecord = $brandCache[$brandId] ?? null;

        if (!$brandRecord) {
            $brandRecord = new CarPriceBrands();
            $brandRecord->setBrandId($brandId);
            $brandRecord->setCode($brandData['code']);
            $brandRecord->setBrandName($brandData['text']);
            $brandRecord->setPopular($brandData['popular']);

            $this->entityManager->persist($brandRecord);
        }

        $years = $this->carPriceService->getBrandYears($brandId);

        if (empty($years)) {
            return;
        }
        $yearsArray = [];
        foreach ($years as $year) {
            $yearsArray[] = $this->carPriceService->getModels($brandId, $year['value']);
            usleep(150 * 1000);
        }
        $models = [];
        foreach ($yearsArray as $year) {
            $models = array_merge($models, $year);
        }

        $models = array_unique($models, SORT_REGULAR);

        $modelIds = array_column($models, 'value');
        $modelsCache = [];

        $records = $this->entityManager->getRepository(CarPriceModels::class)->findBy(['modelId' => $modelIds]);
        array_walk(
            $records,
            static function(CarPriceModels $value) use (&$modelsCache) {
                $modelsCache[$value->getModelId()] = $value;
            }
        );

        $progressBar->start(count($models));
        foreach ($models as $model) {
            usleep(150 * 1000);
            $this->loadModel($brandRecord, $model, $modelsCache);
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->entityManager->flush();
        @$this->entityManager->clear();
    }

    private function loadModel(CarPriceBrands $brand, array $modelData, array $modelsCache)
    {
        $modelRecord = $modelsCache[$modelData['value']] ?? null;
        if (!$modelRecord) {
            $modelRecord = new CarPriceModels();
            $modelRecord->setModelId($modelData['value']);
            $modelRecord->setCode($modelData['code']);
            $modelRecord->setBrand($brand);
            $modelRecord->setModelName($modelData['text']);
            $modelRecord->setStart($modelData['start']);
            $modelRecord->setEnd($modelData['end']);
            $modelRecord->setModelGroupId($modelData['model_group_id']);

            $this->entityManager->persist($modelRecord);
        }

        $modifications = $this
            ->carPriceService
            ->getModification(
                $modelRecord->getBrand()->getBrandId(),
                $modelRecord->getModelId()
            );
        $modifications = array_map(static fn(string $modification) => str_replace("\xc2\xa0",' ', $modification), $modifications);

        $modificationsCache = [];
        $records = $this->entityManager->getRepository(CarPriceModifications::class)->findBy(
            [
                'model' => $modelRecord,
                'modificationName' => $modifications,
            ]
        );
        array_walk(
            $records,
            static function(CarPriceModifications $value) use (&$modificationsCache) {
                $modificationsCache[$value->getModificationName()] = $value;
            }
        );

        foreach ($modifications as $modification) {
            $modificationRecord = $modificationsCache[$modification] ?? null;

            if (!$modificationRecord) {
                $modificationRecord = new CarPriceModifications();

                $modificationRecord->setModel($modelRecord);
                $modificationRecord->setModificationName($modification);

                $this->entityManager->persist($modificationRecord);
            }
        }
    }
}