<?php

namespace App\Domain\Infrastructure\PartnerApi\Repository;

use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use DateTime;
use DealerBundle\Entity\CallbackAction;
use DealerBundle\Entity\DriveOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

class DataSyncRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Получаем запросы пользователей на коммерческие предложения по определенным брендам
     *
     * @param array    $brands
     * @param int|null $from
     *
     * @return array
     */
    public function getOfferRequests(array $brands, ?int $from = null): array
    {
        $this->getEntityManager()->getFilters()->disable('softdeleteable');
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('o', 'client', 'drives', 'driveSchedule', 'driveCar', 'driveEquipment', 'driveModel', 'driveBrand', 'clientCar', 'clientCarModel', 'clientCarBrand', 'dealer', 'd', 'e', 'm', 'brand')
            ->from(DriveOffer::class, 'o')
            ->leftJoin('o.client', 'client')
            ->leftJoin('client.drives', 'drives')
            ->leftJoin('drives.schedule', 'driveSchedule')
            ->leftJoin('driveSchedule.car', 'driveCar')
            ->leftJoin('driveCar.equipment', 'driveEquipment')
            ->leftJoin('driveEquipment.model', 'driveModel')
            ->leftJoin('driveModel.brand', 'driveBrand')
            ->leftJoin('o.clientCar', 'clientCar')
            ->leftJoin('clientCar.model', 'clientCarModel')
            ->leftJoin('clientCarModel.brand', 'clientCarBrand')
            ->leftJoin('o.dealer', 'dealer')
            ->leftJoin('o.dealerCar', 'd')
            ->leftJoin('d.equipment', 'e')
            ->leftJoin('e.model', 'm')
            ->leftJoin('m.brand', 'brand')
            ->where('m.brand IN (:brands)')
            ->setParameter('brands', $brands)
            ->orderBy('o.createdAt', 'DESC');

        if ($from) {
            $qb->andWhere('o.createdAt >= :fromTime')
                ->setParameter('fromTime', (new DateTime())->setTimestamp($from));
        }

        $result = $qb->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        $this->getEntityManager()->getFilters()->enable('softdeleteable');
        return $result;
    }

    /**
     * Получаем запросы пользователей на подписку по определенным брендам
     *
     * @param array    $brands
     * @param int|null $from
     *
     * @return array
     */
    public function getCallbackRequests(array $brands, ?int $from = null): array
    {
        $this->getEntityManager()->getFilters()->disable('softdeleteable');
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'callback',
                'client',
                'drives', 'driveSchedule', 'driveCar', 'driveEquipment', 'driveModel', 'driveBrand',
                'offers', 'offerDealerCar', 'offerEquipment', 'offerModel', 'offerBrand',
                'dealer',
                'd', 'e', 'm', 'brand'
            )
            ->from(CallbackAction::class, 'callback')
            ->leftJoin('callback.client', 'client')
            ->leftJoin('client.drives', 'drives')
            ->leftJoin('drives.schedule', 'driveSchedule')
            ->leftJoin('driveSchedule.car', 'driveCar')
            ->leftJoin('driveCar.equipment', 'driveEquipment')
            ->leftJoin('driveEquipment.model', 'driveModel')
            ->leftJoin('driveModel.brand', 'driveBrand')
            ->leftJoin('client.offers', 'offers')
            ->leftJoin('offers.dealerCar', 'offerDealerCar')
            ->leftJoin('offerDealerCar.equipment', 'offerEquipment')
            ->leftJoin('offerEquipment.model', 'offerModel')
            ->leftJoin('offerModel.brand', 'offerBrand')
            ->leftJoin('callback.dealer', 'dealer')
            ->leftJoin('dealer.brands', 'brands')
            ->leftJoin('callback.dealerCar', 'd')
            ->leftJoin('d.equipment', 'e')
            ->leftJoin('e.model', 'm')
            ->leftJoin('m.brand', 'brand')
            ->where('brands IN (:brands)')
            ->setParameter('brands', $brands)
            ->orderBy('callback.callTime', 'DESC');

        if ($from) {
            $qb->andWhere('callback.callTime >= :fromTime')
                ->setParameter('fromTime', (new DateTime())->setTimestamp($from));
        }

        $result = $qb->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        $this->getEntityManager()->getFilters()->enable('softdeleteable');
        return $result;
    }

    /**
     * Получаем поездки пользователей по определенным брендам
     *
     * @param array    $brands
     * @param int|null $from
     *
     * @return array
     */
    public function getTestDriveRequests(array $brands, ?int $from = null): array
    {
        $this->getEntityManager()->getFilters()->disable('softdeleteable');

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select([
                'drive',
                'client',
                'client_cars',
                'ccars_model',
                'ccars_brand',
                'client_considerations',
                'cconsiderations_model',
                'cconsiderations_brand',
                'schedule',
                'car',
                'equipment',
                'model',
                'brand'
            ])
            ->from(Drive::class, 'drive')
            ->leftJoin('drive.client', 'client')
            ->leftJoin('client.currentCarList', 'client_cars')
            ->leftJoin('client_cars.model', 'ccars_model')
            ->leftJoin('ccars_model.brand', 'ccars_brand')
            ->leftJoin('client.considerationList', 'client_considerations')
            ->leftJoin('client_considerations.model', 'cconsiderations_model')
            ->leftJoin('cconsiderations_model.brand', 'cconsiderations_brand')
            ->leftJoin('drive.schedule', 'schedule')
            ->leftJoin('schedule.car', 'car')
            ->leftJoin('car.equipment', 'equipment')
            ->leftJoin('equipment.model', 'model')
            ->leftJoin('model.brand', 'brand')
            ->where('model.brand IN (:brands)')
            ->andWhere('drive.state IN (6,7)')
            ->setParameter('brands', $brands)
            ->orderBy('drive.start', 'DESC');

        if ($from) {
            $qb->andWhere('drive.createdAt >= :fromTime')
                ->setParameter('fromTime', (new DateTime())->setTimestamp($from));
        }

        $result = $qb->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        $this->getEntityManager()->getFilters()->enable('softdeleteable');

        return $result;
    }
}
