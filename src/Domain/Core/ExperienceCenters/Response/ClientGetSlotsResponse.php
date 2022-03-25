<?php


namespace App\Domain\Core\ExperienceCenters\Response;


use App\Domain\Core\ExperienceCenters\Response\Chunks\SlotResponse;
use App\Entity\ExperienceCenterSchedule;

class ClientGetSlotsResponse
{
    /**
     * @var SlotResponse[]|null
     */
    public ?array $slots;

    public function __construct(array $slots)
    {
        $this->slots = array_map(
            function(ExperienceCenterSchedule $slot) {
                if (!$slot->getIsBooked()) {
                    return new SlotResponse($slot);
                }
            }, $slots
        );
    }
}