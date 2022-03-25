<?php

namespace App\Domain\Core\Model\Service;

use App\Domain\Core\Model\Controller\Builder\ModelResponseBuilder;
use App\Domain\Core\Model\Controller\Response\ModelResponse;
use App\Domain\Core\Model\Controller\Response\ScheduleSlotResponse;
use App\Domain\Core\Model\Repository\ModelRepository;
use App\Domain\Core\System\Service\Security;
use AppBundle\Service\AppConfig;
use CarlBundle\Entity\Schedule;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Service\DroppedView\DroppedCarViewStorage;
use CarlBundle\Service\Integration\Emarsys\Event\Type\DroppedView\DroppedCarViewEmarsysEvent;
use CarlBundle\Service\ScheduleService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Сервис работы с системными моделями авто
 */
class ModelService
{
    private ModelRepository $modelRepository;
    private ScheduleService $scheduleService;
    private ModelResponseBuilder $modelResponseBuilder;
    private Security $security;
    private DroppedCarViewStorage $droppedCarViewStorage;
    private MessageBusInterface $messageBus;
    private AppConfig $appConfig;

    public function __construct(
        ModelRepository $modelRepository,
        ScheduleService $scheduleService,
        ModelResponseBuilder $modelResponseBuilder,
        Security $security,
        DroppedCarViewStorage $droppedCarViewStorage,
        MessageBusInterface $messageBus,
        AppConfig $appConfig
    )
    {
        $this->modelRepository = $modelRepository;
        $this->scheduleService = $scheduleService;
        $this->modelResponseBuilder = $modelResponseBuilder;
        $this->security = $security;
        $this->droppedCarViewStorage = $droppedCarViewStorage;
        $this->messageBus = $messageBus;
        $this->appConfig = $appConfig;
    }

    /**
     * Отдает данные по выбранной модели
     *
     * @param int $modelId
     * @return ModelResponse
     */
    public function get(int $modelId): ModelResponse
    {
        $model = $this->modelRepository->find($modelId);

        if (!$model) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        $allowedBrands = $this->appConfig->getCurrentConfig()['brands'];
        if ($allowedBrands && !in_array($model->getBrand()->getId(), $allowedBrands, true)) {
            throw new AccessDeniedHttpException('Модель недоступна в текущей версии приложения');
        }

        $modelResponse = $this->modelResponseBuilder->build($model);

        // todo Переделать на droppedModelView
        $client = $this->security->getUser();
        if ($client && $client->isClient() && $model->getActiveCar()) {
            $this->droppedCarViewStorage->trackCarView($model->getActiveCar(), $client);
            $this->messageBus->dispatch(new DroppedCarViewEmarsysEvent($client, $model->getActiveCar()), [new DelayStamp(5 * 1000)]);
        }

        return $modelResponse;
    }

    /**
     * Отдает расписание для выбранной модели
     *
     * @param int $modelId
     * @return array
     * @throws InvalidValueException
     */
    public function getSchedule(int $modelId): array
    {
        $model = $this->modelRepository->find($modelId);

        if (!$model) {
            throw new NotFoundHttpException('Модель не найдена');
        }

        $allowedBrands = $this->appConfig->getCurrentConfig()['brands'];
        if ($allowedBrands && !in_array($model->getBrand()->getId(), $allowedBrands, true)) {
            throw new AccessDeniedHttpException('Модель недоступна в текущей версии приложения');
        }

        $car = $model->getActiveCar(true);
        if (!$car) {
            return [];
        }

        return array_map(
            static fn(Schedule $schedule) => new ScheduleSlotResponse($schedule),
            $this->scheduleService->getCarSchedules($car->getId(), time(), time() + (3600 * 24 * 14)) // 4 недели
        );
    }
}
