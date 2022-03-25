<?php

namespace App\Domain\Infrastructure\PartnerApi\Service\DataDTO;

use CarlBundle\Entity\Drive;
use DealerBundle\Entity\CallbackAction;
use DealerBundle\Entity\DriveOffer;

class CallbackDataEntity extends AbstractDataEntity
{
    private int $id;

    private int $callTime;

    private string $firstName;

    private ?string $lastName = null;

    private string $phone;

    private ?string $email = null;

    private ?array $proposals = null;

    private ?array $drives = null;

    public function __construct(CallbackAction $callback)
    {
        $this->id = $callback->getId();
        $this->callTime = $callback->getCallTime()->getTimestamp();
        $this->firstName = $callback->getClient()->getFirstName();
        $this->lastName = $callback->getClient()->getSecondName();
        $this->phone = $callback->getClient()->getPhone();
        $this->email = $callback->getClient()->getEmail();

        /** @var DriveOffer $offer */
        foreach ($callback->getClient()->getOffers()->toArray() as $offer) {
            $this->proposals[] = [
                'id' => $offer->getId(),
                'vin' => $offer->getDealerCar()->getVin(),
                'model' => $offer->getDealerCar()->getEquipment()->getModel()->getName(),
                'date' => $offer->getDealerCar()->getEquipment()->getModel()->getBrand()->getName()
            ];
        }

        /** @var Drive $drive */
        foreach ($callback->getClient()->getFinishedDrives()->toArray() as $drive) {
            $this->drives[] = [
                'id' => $drive->getId(),
                'model' => $drive->getCar()->getModel()->getNameWithBrand(),
                'date' => $drive->getStart()->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('d.m.Y'),
            ];
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCall_time(): int
    {
        return $this->callTime;
    }

    /**
     * @return string
     */
    public function getFirst_name(): string
    {
        return $this->firstName;
    }

    /**
     * @return string|null
     */
    public function getLast_name(): ?string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return array|null
     */
    public function getProposals(): ?array
    {
        return $this->proposals;
    }

    /**
     * @return array|null
     */
    public function getDrives(): ?array
    {
        return $this->drives;
    }
}
