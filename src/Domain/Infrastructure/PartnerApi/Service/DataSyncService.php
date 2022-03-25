<?php

namespace App\Domain\Infrastructure\PartnerApi\Service;

use App\Domain\Infrastructure\PartnerApi\Repository\DataSyncRepository;
use App\Domain\Infrastructure\PartnerApi\Service\DataDTO\CallbackDataEntity;
use App\Domain\Infrastructure\PartnerApi\Service\DataDTO\OfferDataEntity;
use App\Domain\Infrastructure\PartnerApi\Service\DataDTO\TestDriveDataEntity;
use CarlBundle\Entity\Drive;
use DealerBundle\Entity\CallbackAction;
use DealerBundle\Entity\DriveOffer;

class DataSyncService
{
    private DataSyncRepository $repository;

    public function __construct(
        DataSyncRepository $repository
    )
    {
        $this->repository = $repository;
    }

    public function getBrandData(array $brands, ?int $from = null): array
    {
        $data = [
            'drives' => $this->getTestDrives($brands, $from),
            'proposals' => $this->getOffers($brands, $from),
            'callbacks' => $this->getCallbacks($brands, $from)
        ];

        return ['date_sync' => ($from ?: 0),'items' => $data];
    }

    private function getTestDrives(array $brands, ?int $from = null): array
    {
        $testDrives = $this->repository->getTestDriveRequests($brands, $from);

        return array_map(static fn(Drive $testDrive) => new TestDriveDataEntity($testDrive), $testDrives);
    }

    private function getOffers(array $brands, ?int $from = null): array
    {
        $offers = $this->repository->getOfferRequests($brands, $from);

        return array_map(static fn(DriveOffer $offer) => new OfferDataEntity($offer), $offers);
    }

    private function getCallbacks(array $brands, ?int $from = null): array
    {
        $callbacks = $this->repository->getCallbackRequests($brands, $from);

        return array_map(static fn(CallbackAction $callback) => new CallbackDataEntity($callback), $callbacks);
    }
}