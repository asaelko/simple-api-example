<?php
namespace App\Domain\Infrastructure\CarPrice\Command;

use App\Domain\Infrastructure\CarPrice\Service\CarPriceService;
use App\Entity\CarPriceBrands;
use App\Entity\CarPriceCar;
use App\Entity\CarPriceModels;
use App\Entity\CarPriceModifications;
use CarlBundle\Entity\ClientCar;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoadDataForOldCarFromCarPriceCommand extends Command
{
    protected static $defaultName = 'car-price:update-old-cars';

    protected function configure()
    {
        $this
            ->setDescription('Load data from car price data api')
            ->setHelp('Load data from car price data api');
    }

    private ParameterBagInterface $parameterBag;
    private EntityManagerInterface $entityManager;
    private CarPriceService $carPriceService;
    private LoggerInterface $carPriceLogger;

    public function __construct(
        string $name = null,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        CarPriceService $carPriceService,
        LoggerInterface $carPriceLogger
    )
    {
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->carPriceService = $carPriceService;
        $this->carPriceLogger = $carPriceLogger;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cars = $this->entityManager->getRepository(ClientCar::class)->findBy(
            [
                'approved' => true
            ]
        );

        $progressBar = new ProgressBar($output, count($cars));

        foreach ($cars as $car) {
            assert($car instanceof ClientCar);
            $progressBar->advance();
            try {
                $model = $car->getModel()->getName();
                $brand = $car->getModel()->getBrand()->getName();

                $carPriceEntities = $this->carPriceService->mapModelAndBrand($model, $brand);

                if (!$carPriceEntities) {
                    $this->carPriceLogger->error("Mapping error for car with id: {$car->getId()}");
                    continue;
                }

                $carPriceData = $this->carPriceService->createCarInCarPrice($carPriceEntities['brand']->getBrandId(), $car->getManufactureYear(), $carPriceEntities['model']->getModelId(),);

                if (empty($carPriceData)) {
                    $this->carPriceLogger->error("Error get carPrice data for car with id: {$car->getId()}");
                    continue;
                }

                $carPriceCar = new CarPriceCar();
                $carPriceCar->setClientCar($car)
                    ->setCarPriceId($carPriceData['code'])
                    ->setPriceFrom($carPriceData['price_from'])
                    ->setPriceTo($carPriceData['price_to'])
                    ->setHasBonus($carPriceData['has_bonus'])
                ;

                $this->entityManager->persist($carPriceCar);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                continue;
            }
        }
        $progressBar->finish();
        return 0;
    }
}