<?php

namespace App\Domain\Core\Model\Controller\Response;

use CarlBundle\Entity\Schedule;
use OpenApi\Annotations as OA;

class ScheduleSlotResponse
{
    /**
     * Идентификатор расписания, к которому относится данный слот
     *
     * @OA\Property(example=566)
     */
    public int $id;

    /**
     * Время начала слота
     *
     * @OA\Property(example=1612970200)
     */
    public int $start;

    /**
     * Время конца слота
     *
     * @OA\Property(example=1612973800)
     */
    public int $stop;

    public function __construct(Schedule $schedule)
    {
        $this->id = $schedule->getId();
        $this->start = $schedule->getStart()->getTimestamp();
        $this->stop = $schedule->getStop()->getTimestamp();
    }
}
