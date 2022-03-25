<?php


namespace App\Domain\Core\ExperienceCenters\Response;


use App\Domain\Core\ExperienceCenters\Response\Chunks\RequestResponse;
use App\Entity\ExperienceCenterSchedule;
use App\Entity\ExperienceRequest;

class AdminGetClientsRequestsResponse
{
    /**
     * @var int
     */
    public int $id;

    /**
     * @var int
     */
    public int $start;

    /**
     * @var int
     */
    public int $end;

    /**
     * @var RequestResponse[]|null
     */
    public ?array $requests = null;

    public function __construct(ExperienceCenterSchedule $slot, ?array $request)
    {
        $this->id = $slot->getId();
        $this->start = $slot->getStart();
        $this->end = $slot->getEnd();
        if ($request) {
            $this->requests = array_map(
                function (ExperienceRequest $request){
                    return new RequestResponse($request);
                }, $request
            );
        }
    }
}