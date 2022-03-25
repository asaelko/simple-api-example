<?php
namespace App\Domain\Infrastructure\CarPrice\Command;

use App\Domain\Infrastructure\CarPrice\Service\CarPriceService;
use App\Entity\CarPriceBrands;
use App\Entity\CarPriceModels;
use App\Entity\CarPriceModifications;
use App\Entity\CarPriceOffice;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoadCarPriceOfficeCommand extends Command
{
    protected static $defaultName = 'car-price:office:load';

    protected function configure()
    {
        $this
            ->setDescription('Load data from car price data api')
            ->setHelp('Load data from car price data api');
    }

    private ParameterBagInterface $parameterBag;
    private EntityManagerInterface $entityManager;
    private CarPriceService $carPriceService;

    public function __construct(
        string $name = null,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        CarPriceService $carPriceService
    )
    {
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->carPriceService = $carPriceService;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $offices = $this->carPriceService->loadOffices();
            $progressBar = new ProgressBar($output, count($offices));
            foreach ($offices as $office) {
                $progressBar->advance();
                $officeRecords = $this->entityManager->getRepository(CarPriceOffice::class)->findOneBy(
                    [
                        'code' => $office['value'],
                    ]
                );
                if (!$officeRecords) {
                    $officeRecords = new CarPriceOffice();
                }
                $officeRecords->setCode($office['value']);
                $officeRecords->setAddress($office['text']);
                $officeRecords->setIsRemote($office['is_remote']);
                $officeRecords->setLat($office['coords']['latitude']);
                $officeRecords->setLon($office['coords']['longitude']);
                $officeRecords->setStartTime($office['coords']['longitude']);
                $officeRecords->setStartTime($office['startTime']);
                $officeRecords->setFinishTime($office['finishTime']);

                $this->entityManager->persist($officeRecords);
            }
            $this->entityManager->flush();
            $progressBar->finish();
            return 0;
        } catch (Exception $e) {
            return 1;
        }
    }
}