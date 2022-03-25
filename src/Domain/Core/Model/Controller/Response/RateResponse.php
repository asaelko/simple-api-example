<?php

namespace App\Domain\Core\Model\Controller\Response;

use CarlBundle\Entity\DriveRate;
use OpenApi\Annotations as OA;

class RateResponse
{
    /**
     * Уникальный идентификатор тарифа
     *
     * @OA\Property(example=1)
     */
    public int $id;

    /**
     * Наименование тарифа
     *
     * @OA\Property(example="1 000 ₽ (60 минут)")
     */
    public string $name;

    /**
     * Стоимость поездки
     *
     * @OA\Property(example="1 000")
     */
    public float $price;

    /**
     * Форматированная стоимость поездки
     *
     * @OA\Property(example="1 000 ₽")
     */
    public string $formattedPrice;

    /**
     * Продолжительность поездки в минутах
     *
     * @OA\Property(example="60")
     */
    public int $duration;

    /**
     * Форматированная продолжительность поездки
     *
     * @OA\Property(example="60 мин")
     */
    public string $formattedDuration;

    /**
     * Описание тарифа
     *
     * @OA\Property(example="Тест-драйв включает 60 минут вождения за рулем по удобному маршруту и полную презентацию автомобиля. Также у вас будет дополнительное время на заполнение документов и настройки автомобиля под ваши предпочтения.")
     */
    public ?string $description;

    /**
     * Признак необходимости добавления карты для прохождения ТД
     */
    public bool $cardRequired;

    public function __construct(DriveRate $rate)
    {
        $this->id = $rate->getId();
        $this->name = $rate->getName();
        $this->price = $rate->getPrice();
        $this->formattedPrice = $rate->getFormattedPrice();
        $this->duration = $rate->getRideDuration();
        $this->formattedDuration = $rate->getFormattedRideDuration();
        $this->description = $rate->getDescription();
        $this->cardRequired = $rate->isCardRequired();
    }
}
