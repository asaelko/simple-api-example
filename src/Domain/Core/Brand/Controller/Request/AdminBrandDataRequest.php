<?php

namespace App\Domain\Core\Brand\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

/**
 * Запрос на создание или изменение объекта бренда
 */
class AdminBrandDataRequest extends AbstractJsonRequest
{
    /**
     * Наименование бренда
     *
     * @OA\Property(example="BMW")
     *
     * @Assert\NotBlank(message="Не указано название бренда")
     * @Assert\Type(type="string")
     */
    public string $name;

    /**
     * Описание бренда
     *
     * @deprecated надо убрать в следующих версиях API
     *
     * @OA\Property(example="Немецкий автомобильный бренд")
     *
     * @Assert\Type(type="string")
     */
    public ?string $description = null;

    /**
     * Телефон службы поддержки бренда
     *
     * @OA\Property(example="+1 (362) 754-23-111")
     *
     * @Assert\Type(type="string")
     */
    public ?string $phoneSupport = null;

    /**
     * Официальный сайт бренда
     *
     * @OA\Property(example="https://bmw.com")
     *
     * @Assert\Type(type="string")
     */
    public ?string $site = null;

    /**
     * Ссылка на фейсбук бренда
     *
     * @OA\Property(example="https://www.facebook.com/BMW/")
     *
     * @Assert\Type(type="string")
     */
    public ?string $facebook = null;

    /**
     * Ссылка на инстаграм бренда
     *
     * @OA\Property(example="https://www.instagram.com/bmw/")
     *
     * @Assert\Type(type="string")
     */
    public ?string $instagram = null;

    /**
     * Ссылка на страницу на vk.com бренда
     *
     * @OA\Property(example="https://vk.com/bmw")
     *
     * @Assert\Type(type="string")
     */
    public ?string $vk = null;

    /**
     * Массив идентификаторов дилеров, привязанных к бренду
     *
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="integer")
     * )
     *
     * @Assert\Type(type="array")
     */
    public ?array $dealers = null;

    /**
     * Массив идентификаторов моделей, привязанных к бренду
     *
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="integer")
     * )
     *
     * @Assert\Type(type="array")
     */
    public ?array $models = null;

    /**
     * Массив идентификаторов фотографий, привязанных к бренду
     *
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *          @OA\Property(property="type", type="string", description="Тип фотографии", example="logo_bw"),
     *          @OA\Property(property="id", type="integer", description="Идентификатор фото", example=1167)
     *     )
     * )
     *
     * @Assert\Type(type="array")
     */
    public ?array $photos = null;

    /**
     * Массив городов, в которых представлен бренд
     *
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="integer")
     * )
     *
     * @Assert\Type(type="array")
     */
    public ?array $cities = null;
}
