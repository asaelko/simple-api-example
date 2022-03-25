<?php

namespace App\Domain\Core\Subscription\Controller\Admin\Factory;

use App\Domain\Core\Subscription\Controller\Admin\Request\CreateSubscriptionPartnerRequest;
use App\Entity\SubscriptionPartner;

class SubscriptionPartnerFactory
{
    public function fillPartner(SubscriptionPartner $partner, CreateSubscriptionPartnerRequest $request): SubscriptionPartner
    {
        $partner->setName($request->partnerName)
            ->setDescription($request->description)
            ->setFullOrganizationName($request->fullOrganizationName)
            ->setEmail($request->email);

        return $partner;
    }
}
