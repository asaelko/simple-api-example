<?php

namespace App\Domain\Core\Client\Service\RequestDTO;

use App\Entity\LongDrive\LongDriveRequest;
use DateTimeImmutable;
use DateTimeInterface;

class LongDriveDTO extends AbstractRequestDTO
{
    private const TYPE = 'long_drive';

    private int $id;

    private DateTimeImmutable $createdAt;

    private array $client;

    private int $price;

    private array $partner;

    private array $auto;

    public function __construct(LongDriveRequest $longDrive)
    {
        $this->id = $longDrive->getId();

        $this->createdAt = $longDrive->getCreatedAt();

        $this->client = [
            'id' => $longDrive->getClient()->getId(),
        ];

        $this->price = min($longDrive->getModel()->getPrices());

        $this->partner = [
            'id'                     => $longDrive->getPartner()->getId(),
            'name'                   => $longDrive->getPartner()->getName(),
            'description'            => $longDrive->getPartner()->getDescription(),
            'full_organization_name' => $longDrive->getPartner()->getFullOrganizationName(),
            'logo'                   => [
                'absolutePath' => $longDrive->getPartner()->getLogo()->getAbsolutePath(),
            ],
        ];

        $model = $longDrive->getModel()->getModel();

        $this->auto = [
            'id'    => $longDrive->getModel()->getId(),
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
        ];
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function dateTime(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return int
     */
    public function getId(): int
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
     * @return array
     */
    public function getClient(): array
    {
        return $this->client;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @return array
     */
    public function getPartner(): array
    {
        return $this->partner;
    }

    /**
     * @return array
     */
    public function getAuto(): array
    {
        return $this->auto;
    }
}
