<?php

namespace App\Domain\Core\Brand\Controller\Response\Client;

use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;

/**
 * Объект ответа по вложенной модели для клиентского приложения
 */
class ModelResponse
{
    /**
     * Уникальный идентификатор модели
     *
     * @OA\Property(example=566)
     */
    public int $id;

    /**
     * Название модели
     *
     * @OA\Property(example="A5 Sportback")
     */
    public string $name;

    /**
     * Изображение модели для приложения
     *
     * @OA\Property(ref=@DocModel(type=PhotoResponse::class))
     */
    public ?PhotoResponse $photo = null;

    /**
     * Количество машин данной модели в стоках
     *
     * @OA\Property(example=5)
     */
    public int $stocksCount;

    /**
     * Минимальная цена на модель в стоках
     *
     * @OA\Property(example=1230566)
     */
    public ?int $stocksMinPrice = null;

    /**
     * Максимальная цена на модель в стоках
     *
     * @OA\Property(example=2570000)
     */
    public ?int $stocksMaxPrice = null;

    /**
     * Ближайшая доступная дата тест-драйва на данной модели, Unix timestamp
     * Если ТД недоступен - null
     *
     * @OA\Property(example=1612970269)
     */
    public ?int $testDriveTime = null;

    /**
     * Идентификатор автомобиля для тест-драйва
     * Если ТД недоступен - null
     *
     * @OA\Property(example=146)
     */
    public ?int $carId = null;

    /**
     * Количество доступных опций подписки
     *
     * @OA\Property(example=2)
     */
    public int $subscriptionsCount;

    /**
     * Минимальная цена на модель по подписке в месяц
     *
     * @OA\Property(example=120500)
     */
    public ?int $subscriptionsMinPrice = null;

    /**
     * Минимальная срок подписки на модель
     *
     * @OA\Property(example=12)
     */
    public ?int $subscriptionsMinTerm = null;

    /**
     * Для машины доступен запрос на подписку
     *
     * @OA\Property(example=true)
     */
    public bool $hasSubscriptionQuery;

    /**
     * Количество доступных опций лонг-драйва
     *
     * @OA\Property(example=2)
     */
    public int $longDrivesCount;

    /**
     * Минимальная цена на модель на лонг-драйв в день
     *
     * @OA\Property(example=1250)
     */
    public ?int $longDrivesMinPrice = null;

    public function __construct(array $modelData)
    {
        $this->id = $modelData['id'];
        $this->name = trim($modelData['name']);
        $this->stocksCount = $modelData['stocks_count'] ?? 0;
        $this->stocksMinPrice = $modelData['stock_min_price'] ?? null;
        $this->stocksMaxPrice = $modelData['stock_max_price'] ?? null;
        $this->testDriveTime = $modelData['test_drive_time'] ? $modelData['test_drive_time']->getTimestamp() : null;
        $this->carId = $modelData['car_id'] ?? null;

        $this->subscriptionsCount = $modelData['subscription_count'] ?? 0;
        $this->subscriptionsMinPrice = $modelData['subscription_min_price'] ?? null;
        $this->subscriptionsMinTerm = $this->subscriptionsCount > 0 ? 12 : null; // todo поправить хардкод
        $this->hasSubscriptionQuery = $this->subscriptionsCount ? false : $modelData['has_subscription_query'];

        $this->longDrivesCount = $modelData['long_drives_count'] ?? 0;
        $this->longDrivesMinPrice = $modelData['long_drive_min_price'] ?? null;

        if (isset($modelData['photo'])) {
            $this->photo = new PhotoResponse($modelData['photo']);
        }
    }
}
