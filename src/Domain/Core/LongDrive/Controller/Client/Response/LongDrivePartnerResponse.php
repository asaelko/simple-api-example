<?php

namespace App\Domain\Core\LongDrive\Controller\Client\Response;

use App\Entity\LongDrive\LongDrivePartner;

class LongDrivePartnerResponse
{
    public int $id;

    public string $name;

    public string $logo;

    public string $shortDescription;

    public ?string $description;

    public string $fullOrganizationName;

    public function __construct(LongDrivePartner $partner)
    {
        $this->id = $partner->getId();
        $this->name = $partner->getName();
        $this->logo = $partner->getLogo()->getAbsolutePath();
        $this->shortDescription = $partner->getShortDescription();
        $this->description = $partner->getDescription();
        $this->fullOrganizationName = $partner->getFullOrganizationName();
    }
}
