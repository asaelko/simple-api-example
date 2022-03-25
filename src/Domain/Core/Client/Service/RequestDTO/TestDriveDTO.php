<?php

namespace App\Domain\Core\Client\Service\RequestDTO;

use CarlBundle\Entity\Drive;
use CarlBundle\Entity\Photo;
use DateTimeImmutable;
use DateTimeInterface;

class TestDriveDTO extends AbstractRequestDTO
{
    private const TYPE = 'test_drive';

    private int $id;

    private DateTimeImmutable $createdAt;

    private DateTimeImmutable $start;

    private ?DateTimeImmutable $stop;

    private ?DateTimeImmutable $actualStart;

    private ?DateTimeImmutable $actualStop;

    private ?float $startLat;

    private ?float $startLng;

    private ?string $startName;

    private ?string $stopName;

    private int $state;

    private array $client;

    private ?array $driver = null;

    private array $car;

    private array $driveRate;

    private ?string $clientComment = null;

    private array $photos = [];

    private ?int $liked = null;

    private ?float $exterior = null;

    private ?float $interior = null;

    private ?float $consultant = null;

    private ?float $equipment = null;

    private ?int $stockCarsCount = null;

    private ?DateTimeImmutable $prebookingCancelDate = null;

    private int $shared;

    private bool $sharingAvailable;

    public function __construct(Drive $drive)
    {
        $this->id = $drive->getId();

        $createdAt = $drive->getCreatedAt() ?? $drive->getStart();
        $this->createdAt = DateTimeImmutable::createFromMutable($createdAt);

        $this->start = DateTimeImmutable::createFromMutable($drive->getStart());
        $this->stop = $drive->getStop() ? DateTimeImmutable::createFromMutable($drive->getStop()) : null;
        $this->actualStart = $drive->getActualStart() ?: null;
        $this->actualStop = $drive->getActualStop() ?: null;

        $this->startLat = $drive->getStartLat();
        $this->startLng = $drive->getStartLng();
        $this->startName = $drive->getStartName();
        $this->stopName = $drive->getStopName();

        $this->state = $drive->getState();

        $this->client = [
            'id' => $drive->getClient()->getId(),
        ];

        if ($drive->getDriver()) {
            $this->driver = [
                'id' => $drive->getDriver()->getId(),
            ];
        }

        $model = $drive->getCar()->getModel();
        $this->car = [
            'id' => $drive->getCar()->getId(),
            'equipment' => [
                'id'    => $drive->getCar()->getEquipment()->getId(),
                'name'  => $drive->getCar()->getEquipment()->getName(),
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

        $driveRate = $drive->getDriveRate();
        if ($driveRate) {
            $this->driveRate = [
                'id' => $driveRate->getId(),
                'name' => $driveRate->getName(),
                'description' => $driveRate->getDescription(),
                'price' => $driveRate->getPrice(),
                'rideDuration' => $driveRate->getRideDuration(),
                'cancelPenalty' => $driveRate->getCancelPenalty(),
                'formattedRate' => $driveRate->getFormattedPrice(),
                'formattedRideDuration' => $driveRate->getFormattedRideDuration(),
                'formattedCancelPenalty' => $driveRate->getFormattedCancelPenalty(),
            ];
        }

        $this->clientComment = $drive->getClientComment();

        /** @var Photo $photo */
        foreach($drive->getPhotos() as $photo) {
            $this->photos[] = [
                'id' => $photo->getId(),
                'type' => $photo->getType(),
                'absolutePath' => $photo->getAbsolutePath()
            ];
        }

        if ($drive->getFeedback()) {
            $this->liked = (int) $drive->getFeedback()->isLiked();
            $this->exterior = $drive->getFeedback()->getExterior();
            $this->interior = $drive->getFeedback()->getInterior();
            $this->consultant = $drive->getFeedback()->getConsultant();
            $this->equipment = $drive->getFeedback()->getEquipment();
        }

        $this->shared = (int) $drive->getShared();
        $this->sharingAvailable = $drive->isSharingAvailable();

        $this->stockCarsCount = $drive->getStockCarsCount();

        $this->prebookingCancelDate = $drive->getPrebookingCancelDate()
            ? DateTimeImmutable::createFromMutable($drive->getPrebookingCancelDate())
            : null;
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function dateTime(): DateTimeInterface
    {
        return $this->start;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getStop(): ?DateTimeImmutable
    {
        return $this->stop;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getActualStart(): ?DateTimeImmutable
    {
        return $this->actualStart;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getActualStop(): ?DateTimeImmutable
    {
        return $this->actualStop;
    }

    /**
     * @return float|null
     */
    public function getStartLat(): ?float
    {
        return $this->startLat;
    }

    /**
     * @return float|null
     */
    public function getStartLng(): ?float
    {
        return $this->startLng;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @return string|null
     */
    public function getStartName(): ?string
    {
        return $this->startName;
    }

    /**
     * @return string|null
     */
    public function getStopName(): ?string
    {
        return $this->stopName;
    }

    /**
     * @return array
     */
    public function getClient(): array
    {
        return $this->client;
    }

    /**
     * @return array|null
     */
    public function getDriver(): ?array
    {
        return $this->driver;
    }

    /**
     * @return array
     */
    public function getCar(): array
    {
        return $this->car;
    }

    /**
     * @return string|null
     */
    public function getClientComment(): ?string
    {
        return $this->clientComment;
    }

    /**
     * @return array
     */
    public function getPhotos(): array
    {
        return $this->photos;
    }

    /**
     * @return int|null
     */
    public function getLiked(): ?int
    {
        return $this->liked;
    }

    /**
     * @return float|null
     */
    public function getExterior(): ?float
    {
        return $this->exterior;
    }

    /**
     * @return float|null
     */
    public function getInterior(): ?float
    {
        return $this->interior;
    }

    /**
     * @return float|null
     */
    public function getConsultant(): ?float
    {
        return $this->consultant;
    }

    /**
     * @return float|null
     */
    public function getEquipment(): ?float
    {
        return $this->equipment;
    }

    /**
     * @return int
     */
    public function getShared(): int
    {
        return $this->shared;
    }

    /**
     * @return int|null
     */
    public function getStockCarsCount(): ?int
    {
        return $this->stockCarsCount;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getPrebookingCancelDate(): ?DateTimeImmutable
    {
        return $this->prebookingCancelDate;
    }

    /**
     * @return array
     */
    public function getDriveRate(): array
    {
        return $this->driveRate;
    }

    /**
     * @return bool
     */
    public function isSharingAvailable(): bool
    {
        return $this->sharingAvailable;
    }
}
