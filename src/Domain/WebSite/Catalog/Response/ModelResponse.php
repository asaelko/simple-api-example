<?php


namespace App\Domain\WebSite\Catalog\Response;


use App\Domain\Core\Model\Controller\Response\PhotoResponse;
use App\Domain\Core\Model\Controller\Response\RateResponse;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Entity\Photo;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;

class ModelResponse
{
    /**
     * @var PhotoResponse[]
     * @OA\Property(description="Фото модели")
     */
    public array $photo;

    /**
     * @OA\Property(description="Имеются ли тд для данной модели")
     */
    public bool $hasTestDrive;

    /**
     * @OA\Property(description="Идентификатор машины на ТД")
     */
    public ?int $carId = null;

    /**
     * Тариф поездки на данной модели
     *
     * @OA\Property(ref=@DocModel(type=RateResponse::class), nullable=true)
     */
    public ?RateResponse $rate = null;

    /**
     * @OA\Property(description="Есть ли тачка в стоках для покупки")
     */
    public bool $canBuy;

    /**
     * @OA\Property(description="Описание модели")
     */
    public ?string $description;

    /**
     * @OA\Property(description="Тип кузова")
     */
    public ?string $bodyType;

    /**
     * @OA\Property(description="Бренд модлели")
     */
    public string $brand;

    /**
     * @OA\Property(description="Идентификатор бренда")
     */
    public int $brandId;

    /**
     * @OA\Property(description="Минимальная цена")
     */
    public ?string $price;

    /**
     * @OA\Property(description="Название")
     */
    public string $name;

    /**
     * @OA\Property(description="id")
     */
    public int $id;

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

    /**
     * @OA\Property(description="Есть ли возможность доставки до дома")
     */
    public bool $hasDelivery = false;

    /**
     * @OA\Property(description="Есть ли возможность полной оплаты")
     */
    public bool $hasPurchase = false;

    /**
     * @OA\Property(description="Есть ли возможность бронирования")
     */
    public bool $hasBooking = false;

    public function __construct(Model $model, array $stockData, array $tagData = [])
    {
        $this->photo = array_map(
            static fn(Photo $photo) => new PhotoResponse($photo),
            $model->getPhotos()->toArray()
        );
        $this->id = $model->getId();
        $this->description = $model->getDescription();
        $this->brand = $model->getBrand()->getName();
        $this->brandId = $model->getBrand()->getId();
        $this->name = $model->getName();
        $this->bodyType = $model->getTextBodyType();
        $this->hasTestDrive = $model->hasTestDrives();

        $this->carId = $model->getActiveCar() ? $model->getActiveCar()->getId() : null;
        if ($this->carId) {
            $this->rate = new RateResponse($model->getActiveCar()->getTargetDriveRate() ?? $model->getActiveCar()->getDriveRate());
        }

        $this->price = $model->getActiveCar() ? $model->getActiveCar()->getEquipment()->getPrice() : null;

        if (!empty($stockData[$model->getId()])) {
            $this->canBuy = ($stockData[$model->getId()]['stocks_count'] ?? 0) > 0;

            if (!$this->price || $stockData[$model->getId()]['stock_min_price'] < $this->price) {
                $this->price = $stockData[$model->getId()]['stock_min_price'];
            }

            $this->hasDelivery = $stockData[$model->getId()]['has_delivery_ability'] ?? false;
            $this->hasBooking = $stockData[$model->getId()]['has_booking_ability'] ?? false;
            $this->hasPurchase = $stockData[$model->getId()]['has_purchase_ability'] ?? false;
        }

        if (!empty($tagData[$model->getId()])) {
            $this->hasCredit = $tagData[$model->getId()]['loan'];
            $this->hasLeasing = $tagData[$model->getId()]['leasing'];
            $this->hasSubscription = $tagData[$model->getId()]['subscription'];
            if ($this->hasSubscription) {
                $this->minSubscriptionPrice = $tagData[$model->getId()]['subscriptionPrice'];
            }
            $this->hasLongDrive = $tagData[$model->getId()]['longDrive'];
            if ($this->hasLongDrive) {
                $this->minLongDrivePrice = $tagData[$model->getId()]['longDrivePrice'];
            }
        }
    }
}