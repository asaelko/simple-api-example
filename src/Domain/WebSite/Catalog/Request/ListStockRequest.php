<?php

namespace App\Domain\WebSite\Catalog\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class ListStockRequest extends AbstractJsonRequest
{
    /**
     * @Assert\Type(type="array")
     *
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $brandId = [];

    /**
     * @Assert\Type(type="array")
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $modelId = [];

    /**
     * @Assert\Type(type="array")
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $equipmentId = [];

    /**
     * @Assert\Type(type="array")
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $dealerId = [];

    /**
     * @Assert\Type(type="array")
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $colorId = [];

    /**
     * @OA\Property(type="object", properties={
     *     @OA\Property(property="min", type="integer"),
     *     @OA\Property(property="max", type="integer"),
     * })
     *
     * @Assert\Collection(
     *     fields={
     *          "min" = @Assert\Type(type="int"),
     *          "max" = @Assert\Type(type="int")
     *      },
     *     allowExtraFields=false,
     *     allowMissingFields=true,
     *     extraFieldsMessage="Некорректное значение параметра фильтра по году выпуска"
     *)
     */
    public array $year = [];

    /**
     * @OA\Property(type="object", properties={
     *     @OA\Property(property="min", type="integer"),
     *     @OA\Property(property="max", type="integer"),
     * })
     *
     * @Assert\Collection(
     *     fields={
     *          "min" = @Assert\Type(type="int"),
     *          "max" = @Assert\Type(type="int")
     *      },
     *     allowExtraFields=false,
     *     allowMissingFields=true,
     *     extraFieldsMessage="Некорректное значение параметра фильтра по мощности двигателя"
     *)
     */
    public array $horsepower = [];

    /**
     * @OA\Property(type="object", properties={
     *     @OA\Property(property="min", type="integer"),
     *     @OA\Property(property="max", type="integer"),
     * })
     *
     * @Assert\Collection(
     *     fields={
     *          "min" = @Assert\Type(type="int"),
     *          "max" = @Assert\Type(type="int")
     *      },
     *     allowExtraFields=false,
     *     allowMissingFields=true,
     *     extraFieldsMessage="Некорректное значение параметра фильтра по цене"
     *)
     */
    public array $price = [];

    /**
     * @Assert\Type(type="array")
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $wheels = [];

    /**
     * @Assert\Type(type="array")
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $fuelType = [];

    /**
     * @OA\Property(type="object", properties={
     *     @OA\Property(property="min", type="integer"),
     *     @OA\Property(property="max", type="integer"),
     * })
     *
     * @Assert\Collection(
     *     fields={
     *          "min" = @Assert\Type(type="numeric"),
     *          "max" = @Assert\Type(type="numeric")
     *      },
     *     allowExtraFields=false,
     *     allowMissingFields=true,
     *     extraFieldsMessage="Некорректное значение параметр фильтра по объему двигателя"
     *)
     */
    public array $capacity = [];

    /**
     * @Assert\Type(type="array")
     * @OA\Property(type="array", @OA\Items(type="integer"))
     */
    public array $bodyType = [];

    /**
     * @Assert\Type(type="int")
     */
    public ?int $state = null;

    /**
     * @Assert\Type(type="int")
     */
    public ?int $purchase = null;

    /**
     * @Assert\Type(type="int")
     */
    public ?int $delivery = null;

    /**
     * @Assert\Type(type="int")
     */
    public ?int $booking = null;

    /**
     * @Assert\Type(type="integer")
     */
    public ?int $testDrive = null;

    /**
     * @Assert\Type(type="int")
     */
    public ?int $limit = 10;

    /**
     * @Assert\Type(type="int")
     */
    public ?int $offset = 0;

    /**
     * @Assert\Type(type="array")
     * @OA\Property(type="array", @OA\Items(type="string"))
     */
    public array $sort = [];

    /**
     * @Assert\Type(type="string")
     */
    public ?string $fullTextSearch = null;
}
