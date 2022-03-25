<?php

namespace App\Domain\Core\Subscription\Controller\Client\Response;

use App\Entity\SubscriptionPartner;

class SubscriptionPartnerResponse
{
    public int $id;

    public string $name;

    public string $logo;

    public string $shortDescription;

    public ?string $description;

    public string $fullOrganizationName;

    public function __construct(SubscriptionPartner $partner)
    {
        $this->id = $partner->getId();
        $this->name = $partner->getName();
        $this->logo = $partner->getLogo()->getAbsolutePath();
        $this->shortDescription = $partner->getShortDescription();
        $this->description = $partner->getDescription();
        $this->fullOrganizationName = $partner->getFullOrganizationName();
    }
}
