<?php

namespace App\Domain\WebSite\Catalog\Response;

use App\Domain\Core\Model\Controller\Response\BrandResponse;
use App\Domain\Core\Model\Controller\Response\RateResponse;
use CarlBundle\Entity\Model\Model;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;

class RichModelResponse
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
     * Описание модели
     *
     * @OA\Property(example="Компактный кроссовер", nullable=true)
     */
    public ?string $description;

    /**
     * Фотография модели для сайта
     *
     * @OA\Property(example="https://cdn.carl-drive.ru/images/blank.png", nullable=true)
     */
    public ?string $sitePhoto;

    /**
     * Фотография модели для приложения
     *
     * @OA\Property(example="https://cdn.carl-drive.ru/images/blank.png", nullable=true)
     */
    public ?string $appPhoto;

    /**
     * Бренд модели
     *
     * @OA\Property(ref=@DocModel(type=BrandResponse::class))
     */
    public BrandResponse $brand;

    /**
     * Фотографии модели
     *
     * @OA\Property(type="array", @OA\Items(ref=@DocModel(type=PhotoResponse::class)))
     */
    public array $photos = [];

    /**
     * Секции описания модели
     *
     * @OA\Property(type="array", @OA\Items(ref=@DocModel(type=DescriptionSectionResponse::class)))
     */
    public array $sections = [];

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
     * Форматированное описание привода авто
     *
     * @OA\Property(example="Полный")
     */
    public ?string $formattedWheels = null;

    /**
     * Форматированное описание лошадиных сил авто
     *
     * @OA\Property(example="200 л.с.")
     */
    public ?string $formattedPower = null;

    /**
     * Форматированное описание расхода топлива авто
     *
     * @OA\Property(example="7.2 л/100км")
     */
    public ?string $formattedRate = null;

    /**
     * Форматированное описание разгона авто до 100 км/ч
     *
     * @OA\Property(example="6.1 c")
     */
    public ?string $formattedAcceleration = null;

    /**
     * Форматированное описание объема движка авто
     *
     * @OA\Property(example="2000 см³")
     */
    public ?string $formattedCapacity = null;

    /**
     * Флаг возможности тест-драйва на модели
     */
    public bool $testDrive = false;

    /**
     * Ближайшая доступная дата тест-драйва на данной модели, Unix timestamp
     * Если ТД недоступен - null
     *
     * @OA\Property(example=1612970269)
     */
    public ?int $testDriveTime = null;

    /**
     * Название комплектации авто на тест-драйв
     *
     * @OA\Property(example="S Quattro Sport")
     */
    public string $equipmentName;

    /**
     * Тариф поездки на данной модели
     *
     * @OA\Property(ref=@DocModel(type=RateResponse::class), nullable=true)
     */
    public ?RateResponse $rate = null;

    /**
     * Признак подписки на уведомления о появлении новых расписаний по модели
     *
     * @OA\Property(example=false)
     */
    public bool $scheduleNotification = false;

    /**
     * Объект таргетинга. Если он есть – пользователь не подходит под его условия
     *
     * @OA\Property(type="array", @OA\Items(type="object"))
     */
    public ?array $target = null;

    /**
     * Идентификатор активного автомобиля на тест-драйв
     *
     * @var int|null
     */
    public ?int $carId = null;

    /**
     * @var array|null
     *
     * @OA\Property(type="object", properties={
     *     @OA\Property(property="volume", type="integer"),
     *     @OA\Property(property="price", type="integer")
     * })
     */
    public ?array $fuelCard = null;

    /**
     * @OA\Property(description="Можно ли оформить кредит на машину")
     */
    public bool $hasCredit = false;

    /**
     * @OA\Property(description="Можно ли оформить лизинг на машину")
     */
    public bool $hasLeasing = false;

    /**
     * @OA\Property(description="Можно ли оформить подписку на машину")
     */
    public bool $hasSubscription = false;

    /**
     * @OA\Property(description="Минимальная цена подписки, если есть")
     */
    public ?int $minSubscriptionPrice = null;

    /**
     * @OA\Property(description="Можно ли оформить лонг-драйв на машину")
     */
    public bool $hasLongDrive = false;

    /**
     * @OA\Property(description="Минимальная цена лонг-драйва, если есть")
     */
    public ?int $minLongDrivePrice = null;

    public function __construct(Model $model, array $stocksData, array $tagData)
    {
        $activeCar = $model->getActiveCar(true);

        $this->id = $model->getId();
        $this->name = trim($model->getName());
        $this->description = $model->getDescription();
        if ($model->getSitePhoto()) {
            $this->sitePhoto = $model->getSitePhoto()->getAbsolutePath();
        }

        if ($model->getAppPhoto()) {
            $this->appPhoto = $model->getAppPhoto()->getAbsolutePath();
        }

        $this->brand = new BrandResponse($model->getBrand());

        $this->stocksCount = $stocksData['stocks_count'] ?? 0;
        $this->stocksMinPrice = $stocksData['stock_min_price'] ?? null;
        $this->stocksMaxPrice = $stocksData['stock_max_price'] ?? null;

        if (!empty($tagData)) {
            $this->hasCredit = $tagData['loan'];
            $this->hasLeasing = $tagData['leasing'];
            $this->hasSubscription = $tagData['subscription'];
            if ($this->hasSubscription) {
                $this->minSubscriptionPrice = $tagData['subscriptionPrice'];
            }
            $this->hasLongDrive = $tagData['longDrive'];
            if ($this->hasLongDrive) {
                $this->minLongDrivePrice = $tagData['longDrivePrice'];
            }
        }

        if (!$activeCar) {
            return;
        }

        $this->testDrive = true;
        $this->carId = $activeCar->getId();
        $this->formattedWheels = $activeCar->getEquipment()->getFormattedWheels();
        $this->formattedPower = $activeCar->getEquipment()->getFormattedPower();
        $this->formattedRate = $activeCar->getEquipment()->getFormattedRate();
        $this->formattedAcceleration = $activeCar->getEquipment()->getFormattedAcceleration();
        $this->formattedCapacity = $activeCar->getEquipment()->getFormattedCapacity();
        $this->testDriveTime = $activeCar->getFreeScheduleTime();
        $this->equipmentName = $activeCar->getEquipment()->getName();

        $this->fuelCard = [
            'price' => $activeCar->getFreeFuelPrice(),
            'volume' => $activeCar->getFreeFuel()
        ];

        foreach($activeCar->getProfilePhotos() as $photo) {
            $this->photos []= new PhotoResponse($photo);
        }

        foreach($activeCar->getSections() as $section) {
            $this->sections []= new DescriptionSectionResponse($section);
        }

        $this->rate = new RateResponse($activeCar->getTargetDriveRate() ?? $activeCar->getDriveRate());
        $this->target = $activeCar->getCarTarget() ? $activeCar->getCarTarget()->getCheckboxDescription() : null;

        $equipmentPrice = ((int) $activeCar->getEquipment()->getPrice()) ?: null;
        if (!$this->stocksMinPrice || $this->stocksMinPrice > $equipmentPrice) {
            $this->stocksMinPrice = $equipmentPrice;
        }
    }
}
