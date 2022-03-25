<?php


namespace App\Domain\Core\Partners\Request;


use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;


class ListPartnersMarkRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Количество записей для выдачи", example=10)
     * @Assert\Type(type="integer", message="Limit должен быть целым числом")
     * @Assert\GreaterThan(value=0, message="Limit должен быть больше 0")
     */
    public int $limit;

    /**
     * @OA\Property(description="Смещение в выдаче относительно 0", example=1)
     * @Assert\Type(type="integer", message="Offset должен быть целым числом")
     * @Assert\GreaterThanOrEqual(value=0, message="Offset должен быть больше или равен 0")
     */
    public int $offset;

    /**
     * @OA\Property(type="array", description="Возможные на данный момент значения Кредит,Лизинг,Обратный звонок,КП,Бронировние", @OA\Items(type="string", example="Кредит"))
     */
    public array $types = [];

    /**
     * @OA\Property(type="array", description="Список Id партнеров, что бы ограничить выборку лучше использовать вместе с типом", @OA\Items(type="integer", example=1))
     */
    public array $partnersId = [];

    /**
     * @OA\Property(type="array", description="Список оценок", @OA\Items(type="integer", example=1))
     */
    public array $marks = [];

    /**
     * @OA\Property(type="array", description="Список Id клиентов", @OA\Items(type="integer", example=1))
     */
    public array $clientsId = [];

    /**
     * @OA\Property(description="TimeStamp с которого искать записиси", example="1230000")
     */
    public ?int $fromDateCreate = null;

    /**
     * @OA\Property(description="TimeStamp до которого искать записиси", example="1230000")
     */
    public ?int $toDateCreate = null;

    /**
     * @OA\Property(description="TimeStamp с которого искать записиси", example="1230000")
     */
    public ?int $fromDateUpdate = null;

    /**
     * @OA\Property(description="TimeStamp до которого искать записиси", example="1230000")
     */
    public ?int $toDateUpdate = null;
}