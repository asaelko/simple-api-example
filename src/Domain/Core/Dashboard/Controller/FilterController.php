<?php

namespace App\Domain\Core\Dashboard\Controller;

use App\Domain\Core\Dashboard\Request\WidgetDrivesFilterRequest;
use App\Domain\Core\Dashboard\Service\FilterService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class FilterController extends AbstractController
{
    private FilterService $filterService;

    public function __construct(
        FilterService $filterService
    )
    {
        $this->filterService = $filterService;
    }

    /**
     * Получить виджеты, по которым были поездки
     *
     * @OA\Get(operationId="dashboard/filters/drive_widgets")
     *
     * @OA\Response(
     *     response=200,
     *     description="Виджеты, по которым был тест-драйв",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="code",type="string", example="5e4e5a7d7d04b"),
     *                  @OA\Property(property="description", type="string", example="CARL Promo landing"),
     *                  @OA\Property(property="count", type="integer", example=26)
     *              )
     *           )
     *     )
     * )
     *
     * @OA\Tag(name="Dashboard\Filters")
     *
     * @param WidgetDrivesFilterRequest $request
     * @return JsonResponse
     */
    public function getWidgetWithDrivesFilter(WidgetDrivesFilterRequest $request): JsonResponse
    {
        $data = $this->filterService->getWidgetWithDrivesFilter();

        return new JsonResponse($data);
    }
}