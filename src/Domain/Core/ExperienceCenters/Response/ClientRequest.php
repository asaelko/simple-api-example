<?php


namespace App\Domain\Core\ExperienceCenters\Response;


use App\Entity\ExperienceRequest;

class ClientRequest
{
    public int $id;

    public int $start;

    public int $end;

    public int $centerId;

    public string $centerName;

    public string $state;

    public ?string $organizationName;

    public function __construct(ExperienceRequest $request)
    {
        $this->id = $request->getId();
        $this->start = $request->getScheduleSlot()->getStart();
        $this->end = $request->getScheduleSlot()->getEnd();
        $this->centerId = $request->getScheduleSlot()->getExperienceCenter()->getId();
        $this->centerName = $request->getScheduleSlot()->getExperienceCenter()->getName();
        $this->state = $request->stateToString($request->getState());
        $this->organizationName = $request->getScheduleSlot()->getExperienceCenter()->getFullOrganizationName();
    }
}