<?php


namespace App\Domain\Core\ExperienceCenters\Response\Chunks;


use App\Entity\ExperienceCenterSchedule;

class SlotResponse
{
    public int $start;

    public int $end;

    public int $id;

    public float $price;

    public bool $needPay;

    public function __construct(ExperienceCenterSchedule $slot)
    {
        $this->id = $slot->getId();
        $this->start = $slot->getStart();
        $this->end = $slot->getEnd();
        $this->price = $slot->getPrice();
        $this->needPay = $slot->getPrice() > 0;
    }
}