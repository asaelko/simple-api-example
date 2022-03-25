<?php

namespace App\Domain\Core\Model\Controller;

use App\Domain\Core\Model\Controller\Response\ModelResponse;
use App\Domain\Core\Model\Controller\Response\ScheduleSlotResponse;
use App\Domain\Core\Model\Service\ModelService;
use CarlBundle\Exception\InvalidValueException;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Контроллер для получения данных по моделям для клиентов
 */
class Controller extends AbstractController
{
    private ModelService $modelService;

    public function __construct(
        ModelService $modelService
    )
    {
        $this->modelService = $modelService;
    }

    /**
     * Отдаем модель по её идентификатору
     *
     * @OA\Get(operationId="model/get")
     *
     * @OA\Response(
     *     response=200,
     *     description="Запрошенная модель",
     *     @OA\JsonContent(
     *          @OA\Property(type="object", ref=@DocModel(type=ModelResponse::class))
     *     )
     * )
     *
     * @OA\Tag(name="System\Models")
     *
     * @param int $modelId
     * @return JsonResponse
     */
    public function getModel(int $modelId): JsonResponse
    {
        return new JsonResponse($this->modelService->get($modelId));
    }

    /**
     * Отдаем расписание для модели по её идентификатору
     *
     * @OA\Get(operationId="model/schedule")
     *
     * @OA\Response(
     *     response=200,
     *     description="Расписание для модели",
     *     @OA\JsonContent(
     *          @OA\Property(type="array", @OA\Items(ref=@DocModel(type=ScheduleSlotResponse::class)))
     *     )
     * )
     *
     * @OA\Tag(name="System\Models")
     *
     * @param int $modelId
     * @return JsonResponse
     * @throws InvalidValueException
     */
    public function getSchedule(int $modelId): JsonResponse
    {
        return new JsonResponse($this->modelService->getSchedule($modelId));
    }
}
