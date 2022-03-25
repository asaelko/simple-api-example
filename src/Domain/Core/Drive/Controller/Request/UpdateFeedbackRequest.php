<?php

namespace App\Domain\Core\Drive\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Запрос обновления фидбека по поездке после ТД
 */
class UpdateFeedbackRequest extends AbstractJsonRequest
{
    /**
     * Понравилась ли пользователю машина
     */
    public bool $liked = true;

    /**
     * Что именно понравилось или не понравилось в авто?
     * @OA\Property(type="array", @OA\Items(type="string"))
     */
    public array $characteristicsTags = [];

    /**
     * Оценка интерьера автомобиля
     *
     * @OA\Property(type="float", example="0.8", minimum="0", maximum="1")
     * @Assert\Range(min=0, max=1, notInRangeMessage="Оценка интерьера должна быть в пределе от 0 до 1")
     */
    public float $interior = 1.0;

    /**
     * Что именно понравилось или не понравилось в интерьере?
     * @OA\Property(type="array", @OA\Items(type="string"))
     */
    public array $interiorTags = [];

    /**
     * Оценка внешнего вида авто
     *
     * @OA\Property(type="integer", example="0.8", minimum="0", maximum="1")
     * @Assert\Range(min=0, max=1, notInRangeMessage="Оценка внешнего вида должна быть в пределе от 0 до 1")
     */
    public float $exterior = 1.0;

    /**
     * Что именно понравилось или не понравилось во внешнем виде авто?
     * @OA\Property(type="array", @OA\Items(type="string"))
     */
    public array $exteriorTags = [];

    /**
     * Оценка консультанта, проводившего тест-драйв
     *
     * @OA\Property(type="integer", example="0.8", minimum="0", maximum="1")
     * @Assert\Range(min=0, max=1, notInRangeMessage="Оценка консультанта должна быть в пределе от 0 до 1")
     */
    public float $consultant = 1.0;

    /**
     * Что именно понравилось или не понравилось в консультанте?
     * @OA\Property(type="array", @OA\Items(type="string"))
     */
    public array $consultantTags = [];

    /**
     * Оценка комплектации ТД, на которой проходил тест-драйв
     *
     * @OA\Property(type="integer", example="0.8", minimum="0", maximum="1")
     * @Assert\Range(min=0, max=1, notInRangeMessage="Оценка комплектации должна быть в пределе от 0 до 1")
     */
    public float $equipment = 1.0;

    /**
     * @OA\Property(type="bool", example=true)
     * @Assert\Type(type="boolean")
     */
    public bool $brandSubscription = false;
}
