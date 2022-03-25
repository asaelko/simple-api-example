<?php

namespace App\Domain\Core\Model\Controller\Builder;

use App\Domain\Core\Model\Controller\Response\ModelResponse;
use App\Domain\Core\Model\Repository\ModelRepository;
use App\Domain\Core\Model\Service\ScheduleSubscriber;
use App\Entity\Subscription\SubscriptionQuery;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Service\DriveRateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Билдер ответа по запросу модели
 */
class ModelResponseBuilder
{
    private ModelRepository $modelRepository;
    private DriveRateService $driveRateService;
    private Security $security;
    private ScheduleSubscriber $scheduleSubscriber;
    private EntityManagerInterface $entityManager;

    public function __construct(
        Security $security,
        ModelRepository $modelRepository,
        DriveRateService $driveRateService,
        ScheduleSubscriber $scheduleSubscriber,
        EntityManagerInterface $entityManager
    )
    {
        $this->modelRepository = $modelRepository;
        $this->driveRateService = $driveRateService;
        $this->security = $security;
        $this->scheduleSubscriber = $scheduleSubscriber;
        $this->entityManager = $entityManager;
    }

    /**
     * Собираем данные для ответа с запрошенной моделью автомобиля
     *
     * @param Model $model
     * @return ModelResponse
     */
    public function build(Model $model): ModelResponse
    {
        $client = $this->security->getUser();
        if ($client) {
            assert($client instanceof Client);
        }

        $stockData = $this->modelRepository->getStocksDataForModel([$model]);

        $activeCar = $model->getActiveCar(true);
        $rate = null;
        $target = null;
        if ($activeCar) {
            $rate = $this->driveRateService->getDriveRateForUser($client, $model->getActiveCar(true));
            $target = $activeCar->getCarTarget();

            if ($target && $client) {
                $target->setTargetClient($client);

                if ($target->isFit()) {
                    $target = null;
                }
            }
        }

        $subscriptionQuery = $this->entityManager->getRepository(SubscriptionQuery::class)->findOneBy([
            'model' => $model,
            'client' => $client
        ]);

        $modelResponse = new ModelResponse($model, $stockData, $rate, $target, $subscriptionQuery);
        if ($client) {
            $modelResponse->scheduleNotification = (bool) $this->scheduleSubscriber->getSubscription($client, $model);
        }

        return $modelResponse;
    }
}
