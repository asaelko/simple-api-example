<?php

namespace App\Domain\Core\Model\Service;

use App\Entity\Model\ScheduleNotification;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Exception\RestException;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * Сервис подписки на уведомления о появлении новых расписаний по моделям авто
 */
class ScheduleSubscriber extends ServiceEntityRepository
{
    private LoggerInterface $logger;

    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger
    )
    {
        parent::__construct($registry, ScheduleNotification::class);
        $this->logger = $logger;
    }

    /**
     * Получаем подписку пользователя на появление расписания по модели
     *
     * @param Client     $client
     * @param Model|null $model
     *
     * @return ScheduleNotification|null
     */
    public function getSubscription(Client $client, ?Model $model): ?ScheduleNotification
    {
        return $this->getEntityManager()->getRepository(ScheduleNotification::class)->findOneBy([
            'client' => $client,
            'model' => $model,
        ]);
    }

    /**
     * Получаем все подписки для клиента
     *
     * @param Client $client
     * @param array  $filterModels
     *
     * @return array
     */
    public function getClientSubscriptions(Client $client, array $filterModels = []): array
    {
        $filterRequest = ['client' => $client];
        if ($filterModels) {
            $filterRequest['model'] = $filterModels;
        }

        return $this->getEntityManager()->getRepository(ScheduleNotification::class)->findBy($filterRequest);
    }

    /**
     * Подписывает клиента на уведомление о появлении расписания, если у него еще нет активной подписки
     *
     * @param Client        $client
     * @param Model|null    $model
     * @param DateTime|null $requestedTime
     *
     * @return ScheduleNotification|null
     * @throws InvalidValueException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RestException
     */
    public function createSubscription(Client $client, ?Model $model, ?DateTime $requestedTime = null): ?ScheduleNotification
    {
        $subscription = $this->getSubscription($client, $model);
        if ($subscription) {
            if (!$requestedTime) {
                return $subscription;
            }

            if ($subscription->getRequestedTime()) {
                if ($subscription->getRequestedTime()->getTimestamp() !== $requestedTime->getTimestamp()) {
                    $subscription->setRequestedTime($requestedTime);
                    $this->getEntityManager()->flush();
                    throw new RestException('Мы изменили время вашей заявки', 202);
                } else {
                    throw new InvalidValueException('У вас уже есть заявка на это время');
                }
            } else {
                $subscription->setRequestedTime($requestedTime);
                $this->getEntityManager()->flush();
                return $subscription;
            }
        }

        $subscription = new ScheduleNotification();
        $subscription->setClient($client)
            ->setModel($model)
            ->setRequestedTime($requestedTime);

        try {
            $this->getEntityManager()->persist($subscription);
            $this->getEntityManager()->flush();
        } catch (ORMException $e) {
            $this->logger->critical($e);
            return null;
        }

        return $subscription;
    }

    /**
     * Отменяем подписку на уведомления о появлении нового расписания
     *
     * @param Client $client
     * @param Model  $model
     *
     * @return bool
     */
    public function deleteSubscription(Client $client, Model $model): bool
    {
        $subscription = $this->getSubscription($client, $model);
        if (!$subscription) {
            return true;
        }

        try {
            $this->getEntityManager()->remove($subscription);
            $this->getEntityManager()->flush();
        } catch (ORMException $e) {
            $this->logger->critical($e);
            return false;
        }

        return true;
    }

    /**
     * Получаем список уведомлений, готовых к отправке
     *
     * @param Model $model
     *
     * @return ScheduleNotification[]
     */
    public function getRecordsToSendByModel(Model $model): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('n')
            ->from(ScheduleNotification::class, 'n')
            ->leftJoin('n.client', 'c')
            ->where('n.model = :model OR n.model is null')
            ->andWhere('c.deletedAt is null')
            ->setParameter(':model', $model)
            ->getQuery()
            ->getResult();
    }

    /**
     * Смотрим статистику по подпискам на машины
     *
     * @return array
     */
    public function getStatisticsByCars(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('model.id, concat(brand.name, \' \', model.name) as name, count(notifications.id) as notificationsCount')
            ->from(ScheduleNotification::class, 'notifications')
            ->leftJoin('notifications.model', 'model')
            ->leftJoin('model.brand', 'brand')
            ->groupBy('model')
            ->getQuery()
            ->getResult();
    }

    /**
     * Отдает статистику по подпискам на расписания за заданный промежуток времени
     *
     * @param DateTime $rangeStart
     * @param DateTime $rangeEnd
     *
     * @return array
     */
    public function getSubscriptionsCountByTimeRange(DateTime $rangeStart, DateTime $rangeEnd): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select([
                'model.id',
                'concat(brand.name, \' \', model.name) as name',
                'DATE_FORMAT(DATE_ADD(notifications.requestedTime, 3, \'HOUR\'), \'%d.%m.%Y\') as date',
                'count(notifications.id) as notificationsCount',
            ])
            ->from(ScheduleNotification::class, 'notifications')
            ->leftJoin('notifications.model', 'model')
            ->leftJoin('model.brand', 'brand')
            ->where('notifications.requestedTime is not null')
            ->andWhere('notifications.requestedTime BETWEEN :start AND :end')
            ->setParameter('start', $rangeStart)
            ->setParameter('end', $rangeEnd)
            ->orderBy('date')
            ->groupBy('model.id', 'model.name', 'date')
            ->getQuery()
            ->getResult();
    }

    /**
     * Отдает список подписок на конкретные дни
     *
     * @param $limit
     * @param $offset
     *
     * @return array
     */
    public function getSubscriptionsWithRequestedTime($limit, $offset): array
    {
        $this->getEntityManager()->getFilters()->disable('softdeleteable');
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select(['notifications', 'model', 'brand', 'client'])
            ->from(ScheduleNotification::class, 'notifications')
            ->leftJoin('notifications.model', 'model')
            ->leftJoin('model.brand', 'brand')
            ->leftJoin('notifications.client', 'client')
            ->where('notifications.requestedTime is not null')
            ->andWhere('notifications.requestedTime > NOW()')
            ->orderBy('notifications.requestedTime', 'ASC');

        $items = $query->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();

        try {
            $count = $query->select(['COUNT(notifications)'])->getQuery()->getSingleScalarResult();
        } catch (ORMException $e) {
            $count = count($items);
        }

        $this->getEntityManager()->getFilters()->enable('softdeleteable');
        return ['items' => $items, 'count' => $count];
    }
}
