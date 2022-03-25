<?php

namespace App\Domain\Infrastructure\PartnerApi\Service\DataDTO;

use CarlBundle\Entity\ClientCar;
use CarlBundle\Entity\ClientConsideration;
use CarlBundle\Entity\Drive;
use DateTimeImmutable;

class TestDriveDataEntity extends AbstractDataEntity
{
    private int $id;

    private string $model;

    private DateTimeImmutable $date;

    private string $firstName;

    private ?string $lastName = null;

    private string $phone;

    private ?string $email = null;

    private ?int $budgetFrom = null;

    private ?int $budgetTo = null;

    private ?int $purchasePeriodFrom = null;

    private ?int $purchasePeriodTo = null;

    private ?bool $needCredit = null;

    private ?bool $needLeasing = null;

    private ?bool $needSubscription = null;

    private ?array $clientCars = [];

    private ?array $considerations = [];


    public function __construct(Drive $drive)
    {
        $this->id = $drive->getId();
        $this->model = $drive->getCar()->getModel()->getNameWithBrand();
        $this->date = DateTimeImmutable::createFromMutable($drive->getStart());

        $client = $drive->getClient();
        $this->firstName = trim($client->getFirstName());
        $this->lastName = trim($client->getSecondName());
        $this->phone = $client->getPhone();
        $this->email = $client->getEmail();
        $this->budgetFrom = $client->getBudgetFrom();
        $this->budgetTo = $client->getBudgetTo();
        $this->purchasePeriodFrom = $client->getPurchasePeriodFrom();
        $this->purchasePeriodTo = $client->getPurchasePeriodTo();
        $this->needCredit = $client->getNeedCredit();
        $this->needLeasing = $client->getNeedLeasing();
        $this->needSubscription = $client->getWantSubscription();

        /** @var ClientCar $car */
        foreach($client->getApprovedCurrentCarList() as $car) {
            $this->clientCars[] = [
                'brand' => $car->getModel()->getBrand()->getName(),
                'model' => $car->getModel()->getName(),
                'year' => $car->getManufactureYear(),
                'horsepower' => $car->getHorsepower(),
            ];
        }

        /** @var ClientConsideration $car */
        foreach($client->getConsiderationList() as $car) {
            $this->considerations[] = [
                'brand' => $car->getModel()->getBrand()->getName(),
                'model' => $car->getModel()->getName(),
                'need_credit' => $car->isNeedCredit(),
                'new' => $car->getIsNew(),
            ];
        }
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
    public function getDate(): string
    {
        return $this->date->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('d.m.Y');
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
     * @return int|null
     */
    public function getBudget_from(): ?int
    {
        return $this->budgetFrom;
    }

    /**
     * @return int|null
     */
    public function getBudget_to(): ?int
    {
        return $this->budgetTo;
    }

    /**
     * @return int|null
     */
    public function getPurchase_period_from(): ?int
    {
        return $this->purchasePeriodFrom;
    }

    /**
     * @return int|null
     */
    public function getPurchase_period_to(): ?int
    {
        return $this->purchasePeriodTo;
    }

    /**
     * @return bool|null
     */
    public function getNeed_credit(): ?bool
    {
        return $this->needCredit;
    }

    /**
     * @return bool|null
     */
    public function getNeed_leasing(): ?bool
    {
        return $this->needLeasing;
    }

    /**
     * @return bool|null
     */
    public function getNeed_subscription(): ?bool
    {
        return $this->needSubscription;
    }

    /**
     * @return array|null
     */
    public function getClient_cars(): ?array
    {
        return $this->clientCars;
    }

    /**
     * @return array|null
     */
    public function getConsiderations(): ?array
    {
        return $this->considerations;
    }
}
