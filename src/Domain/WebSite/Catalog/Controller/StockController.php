<?php

namespace App\Domain\WebSite\Catalog\Controller;

use App\Domain\WebSite\Catalog\Request\AnonymousBookingRequest;
use App\Domain\WebSite\Catalog\Request\ListStockRequest;
use App\Domain\WebSite\Catalog\Response\StockCarWithDriveDataResponse;
use App\Domain\WebSite\Catalog\Response\StockResponse;
use App\Domain\WebSite\Catalog\Service\StockBookingService;
use App\Domain\WebSite\Catalog\Service\StockListService;
use JsonException;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Контроллер просмотра каталога дилерских стоков
 */
class StockController extends AbstractController
{
    private StockListService $listService;
    private StockBookingService $bookingService;

    public function __construct(
        StockListService    $listService,
        StockBookingService $bookingService
    )
    {
        $this->listService = $listService;
        $this->bookingService = $bookingService;
    }

    /**
     * Получение списка авто из стока для каталога с фильтрами
     *
     * @OA\Post(
     *     operationId="/web/catalog/stock/list",
     *     @OA\RequestBody(
     *          @DocModel(type=ListStockRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список автомобилей дилеров с учетом фильров",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=StockResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Web\Catalog")
     *
     * @param ListStockRequest $request
     *
     * @return StockResponse
     */
    public function listAction(ListStockRequest $request): StockResponse
    {
        return $this->listService->list($request);
    }

    /**
     * Получение авто дилера по его ID
     *
     * @OA\Get(
     *     operationId="/web/catalog/stock/show"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет автомобиль дилера",
     *     @OA\JsonContent(
     *        ref=@DocModel(type=StockCarWithDriveDataResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Web\Catalog")
     *
     * @param int $stockId
     *
     * @return StockCarWithDriveDataResponse
     */
    public function showAction(int $stockId): StockCarWithDriveDataResponse
    {
        return $this->listService->show($stockId);
    }

    /**
     * Бронирование машины из стока по ID
     *
     * @OA\Post(
     *     operationId="/web/catalog/stock/book",
     *     @OA\RequestBody(
     *          @DocModel(type=AnonymousBookingRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Результат бронирования машины дилера",
     *     @OA\JsonContent(
     *         @OA\Property(
     *              property="result",
     *              type="bool",
     *              example=true
     *        ),
     *        @OA\Property(
     *              property="redirectURL",
     *              type="string",
     *              example="https://sitev2.test.carl-drive.ru/stock/132"
     *        ),
     *        @OA\Property(
     *              property="transactionId",
     *              type="string",
     *              example="34dd6b73c4e446f785bd7cd258ba576d"
     *        )
     *     )
     * )
     *
     * @OA\Tag(name="Web\Catalog")
     *
     * @param int $stockId
     *
     * @return array
     * @throws JsonException
     */
    public function bookAction(AnonymousBookingRequest $request, int $stockId): array
    {
        return $this->bookingService->book($request, $stockId);
    }
}
