<?php

namespace App\Domain\Core\Client\Service\RequestDTO;

use App\Entity\PartnersMark;
use DateTimeImmutable;
use DateTimeInterface;
use DealerBundle\Entity\DriveOffer;

class OfferRequestDTO extends AbstractRequestDTO
{
    private const TYPE = 'offer';

    private int $id;

    private array $client;

    private array $dealerCar;

    private ?int $price;
    private ?string $formattedPrice = null;
    private ?string $formattedDiscount = null;

    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $expirationAt;
    private ?DateTimeImmutable $respondedAt;

    private ?string $dealerComment = null;

    private array $services;

    private array $feedback = [];

    private bool $booked;
    private ?DateTimeImmutable $bookedUntil = null;
    private bool $bookingAbility;
    private bool $purchaseAbility;
    private bool $purchased;

    public function __construct(DriveOffer $offer, ?PartnersMark $partnersMark = null)
    {
        $this->id = $offer->getId();
        $this->createdAt = DateTimeImmutable::createFromMutable($offer->getCreatedAt());
        if ($offer->getDealerCar()->getDeletedAt()) {
            $this->expirationAt = DateTimeImmutable::createFromMutable($offer->getDealerCar()->getDeletedAt());
        } else {
            $this->expirationAt = $offer->getExpirationAt() ? DateTimeImmutable::createFromMutable($offer->getExpirationAt()) : null;
        }
        $this->respondedAt = $offer->getRespondedAt() ? DateTimeImmutable::createFromMutable($offer->getRespondedAt()) : null;

        $this->client = [
            'id' => $offer->getClient()->getId(),
        ];

        $model = $offer->getDealerCar()->getEquipment()->getModel();
        $this->dealerCar = [
            'id'        => $offer->getDealerCar()->getId(),
            'vin'       => $offer->getDealerCar()->getVin(),
            'dealer'    => [
                'id'      => $offer->getDealer()->getId(),
                'name'    => $offer->getDealer()->getName(),
                'address' => $offer->getDealer()->getAddress(),
                'bookingAbility' => $offer->getDealer()->getHasBookingAbility(),
                'purchaseAbility' => $offer->getDealer()->getHasPurchaseAbility(),
                'deliveryAbility' => $offer->getDealer()->getHasDeliveryAbility()
            ],
            'equipment' => [
                'id'    => $offer->getDealerCar()->getEquipment()->getId(),
                'name'  => $offer->getDealerCar()->getEquipment()->getName(),
                'model' => [
                    'id'       => $model->getId(),
                    'name'     => $model->getName(),
                    'appPhoto' => [
                        'absolutePath' => $model->getAppPhoto() ? $model->getAppPhoto()->getAbsolutePath() : null,
                    ],
                    'brand'    => [
                        'id'   => $model->getBrand()->getId(),
                        'name' => $model->getBrand()->getName(),
                    ],
                ],
            ],
        ];

        $this->price = $offer->getPrice() ?? $offer->getDealerCar()->getPrice();
        $this->formattedPrice = sprintf('%s ₽', number_format($this->price, 0, '.', ' '));
        $this->formattedDiscount = $offer->getFormattedDiscount();

        $this->services = $offer->getServices();

        $this->dealerComment = $offer->getDealerComment();

        if ($partnersMark) {
            $this->feedback = [
                'id'   => $partnersMark->getId(),
                'mark' => $partnersMark->getMark(),
            ];
        }

        $this->booked = $offer->isBooked();
        if ($offer->getBookedUntil()) {
            $this->bookedUntil = DateTimeImmutable::createFromMutable($offer->getBookedUntil());
        }
        $this->bookingAbility = $offer->getBookingAbility();
        $this->purchaseAbility = $offer->getPurchaseAbility();
        $this->purchased = $offer->isPurchased();
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

    public function getExpirationAt(): ?DateTimeImmutable
    {
        return $this->expirationAt;
    }

    public function getRespondedAt(): ?DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function getFormattedPrice(): ?string
    {
        return $this->formattedPrice;
    }

    public function getFormattedDiscount(): ?string
    {
        return $this->formattedDiscount;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getFeedback(): array
    {
        return $this->feedback;
    }

    public function getClient(): array
    {
        return $this->client;
    }

    public function getDealerCar(): array
    {
        return $this->dealerCar;
    }

    public function getDealerComment(): ?string
    {
        return $this->dealerComment;
    }

    public function isBooked(): bool
    {
        return $this->booked;
    }

    public function getBookedUntil(): ?DateTimeImmutable
    {
        return $this->bookedUntil;
    }

    public function isBookingAbility(): bool
    {
        return $this->bookingAbility;
    }

    public function isPurchaseAbility(): bool
    {
        return $this->purchaseAbility;
    }

    public function isPurchased(): bool
    {
        return $this->purchased;
    }
}
