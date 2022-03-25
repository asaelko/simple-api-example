<?php


namespace App\Domain\Core\ExperienceCenters\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class AdminCreateScheduleForCenter extends AbstractJsonRequest
{
    /**
     * @var int
     * @Assert\Type(type="integer", message="Не правильный формат centerId")
     * @Assert\NotBlank(message="Не передан id центра")
     */
    public int $centerId;

    /**
     * @var int
     * @Assert\Type(type="integer", message="Дата начала должна быть в timestamp")
     * @Assert\NotBlank(message="Не указана дата начала")
     */
    public int $start;

    /**
     * @var int
     * @Assert\Type(type="integer", message="Продолжительность должна быть целым числом (в минутах)")
     * @Assert\NotBlank(message="Не указана продолжительность сеанса")
     */
    public int $duration;

    /**
     * @var int
     * @Assert\Type(type="integer", message="Цена должна быть целым числом")
     * @Assert\NotBlank(message="Не указана цена")
     */
    public int $price;
}