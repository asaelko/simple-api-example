<?php

namespace App\Domain\Core\Client\Service\RequestDTO;

use App\Entity\PartnersMark;
use App\Entity\Subscription\SubscriptionQuery;
use App\Entity\SubscriptionRequest;
use DateTimeImmutable;
use DateTimeInterface;

class SubscriptionQueryDTO extends AbstractRequestDTO
{
    private const TYPE = 'subscription_query';

    private int $id;

    private DateTimeImmutable $createdAt;

    private string $modelName;

    private ?string $photo;

    private array $feedback = [];

    public function __construct(SubscriptionQuery $request, ?PartnersMark $partnersMark = null)
    {
        $this->id = $request->getId();
        $this->createdAt = $request->getCreatedAt();
        $this->modelName = $request->getModel()->getNameWithBrand();
        if ($request->getModel()->getAppPhoto()) {
            $this->photo = $request->getModel()->getAppPhoto()->getAbsolutePath();
        }

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

    public function getFeedback(): array
    {
        return $this->feedback;
    }
}
