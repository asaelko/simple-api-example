<?php


namespace App\Domain\Core\ExperienceCenters\Response\Chunks;


use App\Entity\ExperienceRequest;

class RequestResponse
{
    public ?int $clientId = null;

    public ?string $firstName = null;

    public ?string $lastName = null;

    public ?string $phone = null;

    public int $id;

    public string $state;

    public function __construct(
        ExperienceRequest $request
    )
    {
        $this->id = $request->getId();
        $this->state = $request->stateToString($request->getState());

        $this->clientId = $request->getClient() ? $request->getClient()->getId() : null;
        $this->firstName = $request->getClient() ? $request->getClient()->getFirstName() : null;
        $this->lastName = $request->getClient() ? $request->getClient()->getSecondName() : null;
        $this->phone = $request->getClient() ? $request->getClient()->getPhone() : null;
    }
}