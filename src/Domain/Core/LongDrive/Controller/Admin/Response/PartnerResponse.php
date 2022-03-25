<?php

namespace App\Domain\Core\LongDrive\Controller\Admin\Response;

use App\Domain\Core\Story\Controller\Response\MediaResponse;
use App\Entity\LongDrive\LongDrivePartner;

class PartnerResponse
{
    public int $id;

    public string $partnerName;

    public string $description;

    public string $fullOrganizationName;

    public string $email;

    public string $shortDescription;

    public MediaResponse $logo;

    public function __construct(
        LongDrivePartner $partner
    )
    {
        $this->id = $partner->getId();
        $this->partnerName = $partner->getName();
        $this->description = $partner->getDescription();
        $this->shortDescription = $partner->getShortDescription();
        $this->fullOrganizationName = $partner->getFullOrganizationName();
        $this->email = $partner->getEmail();
        $this->logo = new MediaResponse($partner->getLogo());
    }
}