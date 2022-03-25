<?php

namespace App\Domain\Core\Client\Service\RequestDTO;

use App\Entity\PartnersMark;
use App\Entity\SubscriptionRequest;
use DateTimeImmutable;
use DateTimeInterface;

class SubscriptionRequestDTO extends AbstractRequestDTO
{
    private const TYPE = 'subscription';

    private int $id;

    private DateTimeImmutable $createdAt;

    private string $modelName;

    private ?string $photo;

    private string $price;

    private string $term;

    private string $contractSum;

    private array $partner;

    private array $feedback = [];

    public function __construct(SubscriptionRequest $request, ?PartnersMark $partnersMark = null)
    {
        $this->id = $request->getId();
        $this->createdAt = $request->getCreatedAt();
        $this->modelName = $request->getModel()->getModel()->getNameWithBrand();
        if ($request->getModel()->getModel()->getAppPhoto()) {
            $this->photo = $request->getModel()->getModel()->getAppPhoto()->getAbsolutePath();
        }

        $this->price = sprintf('%s ₽', number_format($request->getPrice(), 0, '.', ' '));
        $this->term = sprintf('%d мес', $request->getTerm());
        $this->contractSum = sprintf('%s ₽', number_format($request->getContractSum(), 0, '.', ' '));

        $this->partner = [
            'name' => $request->getPartner()->getName(),
            'shortDescription' => $request->getPartner()->getShortDescription()
        ];

        if ($partnersMark) {
            $this->feedback = [
                'id' => $partnersMark->getId(),
                'mark' => $partnersMark->getMark()
            ];
        }
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function dateTime(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getTerm()
    {
        return $this->term;
    }

    public function getContractSum(): string
    {
        return $this->contractSum;
    }

    public function getPartner(): array
    {
        return $this->partner;
    }

    public function getFeedback(): array
    {
        return $this->feedback;
    }
}
