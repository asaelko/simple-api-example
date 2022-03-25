<?php


namespace App\Domain\Core\ExperienceCenters\Response;


use App\Entity\ExperienceCenter;

class AdminGetCenterResponse
{
    public int $id;

    public int $brandId;

    public string $brandName;

    public string $name;

    public string $description;

    public string $shortDescription;

    public string $email;

    public ?string $organizationName;

    public function __construct(ExperienceCenter $center)
    {
        $this->id = $center->getId();
        $this->brandId = $center->getBrand()->getId();
        $this->name = $center->getName();
        $this->brandName = $center->getBrand()->getName();
        $this->description = $center->getDescription();
        $this->shortDescription = $center->getShortDescription();
        $this->email = $center->getEmailToSendRequest();
        $this->organizationName = $center->getFullOrganizationName();
    }
}