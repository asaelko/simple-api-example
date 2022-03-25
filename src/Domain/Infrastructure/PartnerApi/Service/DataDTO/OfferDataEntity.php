<?php

namespace App\Domain\Infrastructure\PartnerApi\Service\DataDTO;

use CarlBundle\Entity\Drive;
use DealerBundle\Entity\DriveOffer;

class OfferDataEntity extends AbstractDataEntity
{
    private int $id;

    private string $vin;

    private string $model;

    private string $first_name;

    private ?string $last_name = null;

    private string $phone;

    private ?string $email = null;

    private ?bool $need_credit = null;

    private ?int $initial_fee = null;

    private ?bool $need_registration = null;

    private ?bool $need_osago = null;

    private ?bool $need_delivery = null;

    private ?array $trade_in = null;

    private ?array $drives = null;

    public function __construct(DriveOffer $offer)
    {
        $this->id = $offer->getId();

        $this->vin = $offer->getDealerCar()->getVin();
        $this->model = $offer->getDealerCar()->getEquipment()->getModel()->getNameWithBrand();

        $client = $offer->getClient();
        $this->first_name = trim($client->getFirstName());
        $this->last_name = trim($client->getSecondName());
        $this->phone = $client->getPhone();
        $this->email = $client->getEmail();

        $this->need_credit = $offer->isNeedCredit();
        $this->initial_fee = $offer->getInitialFee();
        $this->need_registration = $offer->getNeedRegistration();
        $this->need_osago = $offer->getNeedOsago();
        $this->need_delivery = $offer->getNeedDelivery();
        if ($offer->getClientCar()) {
            $this->trade_in = [
                'brand'      => $offer->getClientCar()->getModel()->getBrand()->getName(),
                'model'      => $offer->getClientCar()->getModel()->getName(),
                'year'       => $offer->getClientCar()->getManufactureYear(),
                'horsepower' => $offer->getClientCar()->getHorsepower(),
            ];
        }

        /** @var Drive $drive */
        foreach ($client->getFinishedDrives()->toArray() as $drive) {
            $this->drives[] = [
                'id' => $drive->getId(),
                'model' => $drive->getCar()->getModel()->getNameWithBrand(),
                'date' => $drive->getStart()->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('d.m.Y'),
            ];
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getVin(): ?string
    {
        return $this->vin;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getFirst_name(): string
    {
        return $this->first_name;
    }

    /**
     * @return string|null
     */
    public function getLast_name(): ?string
    {
        return $this->last_name;
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
     * @return bool|null
     */
    public function getNeed_credit(): ?bool
    {
        return $this->need_credit;
    }

    /**
     * @return int|null
     */
    public function getInitial_fee(): ?int
    {
        return $this->initial_fee;
    }

    /**
     * @return bool|null
     */
    public function getNeed_registration(): ?bool
    {
        return $this->need_registration;
    }

    /**
     * @return bool|null
     */
    public function getNeed_osago(): ?bool
    {
        return $this->need_osago;
    }

    /**
     * @return bool|null
     */
    public function getNeed_delivery(): ?bool
    {
        return $this->need_delivery;
    }

    /**
     * @return array|null
     */
    public function getTrade_in(): ?array
    {
        return $this->trade_in;
    }

    /**
     * @return array|null
     */
    public function getDrives(): ?array
    {
        return $this->drives;
    }
}
