<?php

namespace App\Domain\Core\Subscription\Controller\Admin\Response;

use App\Entity\SubscriptionPartner;

class PartnerResponse
{
    public ?int $id;

    public ?string $partnerName;

    public ?string $description;

    public ?string $fullOrganizationName;

    public ?string $email;

    public function __construct(
        SubscriptionPartner $partner
    )
    {
        $this->id = $partner->getId();
        $this->partnerName = $partner->getName();
        $this->description = $partner->getDescription();
        $this->fullOrganizationName = $partner->getFullOrganizationName();
        $this->email = $partner->getEmail();
    }
}