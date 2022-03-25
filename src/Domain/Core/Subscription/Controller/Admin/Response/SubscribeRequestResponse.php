<?php


namespace App\Domain\Core\Subscription\Controller\Admin\Response;


use App\Entity\SubscriptionRequest;

class SubscribeRequestResponse
{
    public int $id;

    public int $createdAt;

    public int $clientId;

    public string $fullName;

    public string $partner;

    public int $modelId;

    public int $brandId;

    public string $modelName;

    public int $term;

    public float $price;

    public ?int $mark;

    public function __construct(
        SubscriptionRequest $request,
        ?int $mark
    )
    {
        $this->id = $request->getId();
        $this->createdAt = $request->getCreatedAt()->getTimestamp();
        $this->clientId = $request->getClient()->getId();
        $this->fullName = $request->getClient()->getFullName();
        $this->partner = $request->getPartner()->getName();
        $this->modelId = $request->getModel()->getModel()->getId();
        $this->modelName = $request->getModel()->getModel()->getNameWithBrand();
        $this->brandId = $request->getModel()->getModel()->getBrand()->getId();
        $this->term = $request->getTerm();
        $this->price = $request->getPrice();

        $this->mark = $mark;
    }
}