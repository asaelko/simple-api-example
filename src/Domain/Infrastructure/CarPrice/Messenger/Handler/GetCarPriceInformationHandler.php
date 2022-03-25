<?php


namespace App\Domain\Infrastructure\CarPrice\Messenger\Handler;


use App\Domain\Infrastructure\CarPrice\Messenger\Message\GetCarPriceInformationMessage;
use App\Domain\Infrastructure\CarPrice\Service\CarPriceService;
use App\Entity\CarPriceCar;
use CarlBundle\Entity\ClientCar;
use CarlBundle\Entity\Model\Model;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GetCarPriceInformationHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;

    private LoggerInterface $carPriceLogger;

    private CarPriceService $carPriceService;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $carPriceLogger,
        CarPriceService $carPriceService
    )
    {
        $this->entityManager = $entityManager;
        $this->carPriceLogger = $carPriceLogger;
        $this->carPriceService = $carPriceService;
    }

    public function __invoke(GetCarPriceInformationMessage $message)
    {
        try {
            $car = $this->entityManager->getRepository(ClientCar::class)->find($message->getCarId());

            if (!$car) {
                $this->carPriceLogger->error("Client car with id: {$message->getCarId()} not found");
                return false;
            }

            $model = $car->getModel()->getName();
            $brand = $car->getModel()->getBrand()->getName();

            $carPriceEntities = $this->carPriceService->mapModelAndBrand($model, $brand);

            if (!$carPriceEntities) {
                $this->carPriceLogger->error("Mapping error for car with id: {$message->getCarId()}");
                return false;
            }

            $carPriceData = $this->carPriceService->createCarInCarPrice($carPriceEntities['brand']->getBrandId(), $car->getManufactureYear(), $carPriceEntities['model']->getModelId(),);

            if (empty($carPriceData)) {
                $this->carPriceLogger->error("Error get carPrice data for car with id: {$message->getCarId()}");
                return false;
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
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}