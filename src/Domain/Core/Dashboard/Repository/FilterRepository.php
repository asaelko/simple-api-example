<?php

namespace App\Domain\Core\Dashboard\Repository;

use App\Domain\Core\Dashboard\EventListener\WidgetFilterListener;
use App\Domain\Core\Dashboard\Events\WidgetWithDrivesFilterEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Репозиторий для получения фильтров для дешборда
 */
class FilterRepository
{
    private EntityManagerInterface $entityManager;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface   $entityManager,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Получаем список виджетов, по которым был тест-драйв
     *
     * @param array $widgets
     *
     * @return array
     */
    public function getWidgetWithDrivesFilter(array $widgets = []): array
    {

        $event = new WidgetWithDrivesFilterEvent();
        $this->eventDispatcher->dispatch($event, WidgetWithDrivesFilterEvent::WIDGET_WITH_DRIVES_FILTER_EVENT);
        $dateStart = $event->getDateStart();
        $dateEnd = $event->getDateEnd();

        $dateWhere = '';
        if ($dateStart) {
            $dateWhere .= 'and drives.start >= DATE_FORMAT("' . $dateStart->format('Y-m-d') . '", GET_FORMAT(DATETIME, "ISO"))';
        }
        if ($dateEnd) {
            $dateWhere .= ' and drives.start <= ADDTIME(DATE_FORMAT("' . $dateEnd->format('Y-m-d') . '", GET_FORMAT(DATETIME, "ISO")), "23:59:59")';
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $sqlFilter = '';
        if ($widgets) {
            $widgets = array_map(static fn(string $widgetCode) => $qb->expr()->literal($widgetCode), $widgets);
            $sqlFilter = 'and widget.id IN (' . implode(', ', $widgets) . ')';
        }

        $sql = <<<SQL
                SELECT
                    widget.id,
                    widget.description,
                    count(drives.id) as 'count'
                FROM drives
                left join widget on drives.regWidgetCode = widget.id
                where drives.deletedAt is null and widget.id is not null {$sqlFilter} {$dateWhere} 
                group by widget.id, widget.description
                having count(drives.id) > 0
                order by widget.description
        SQL;

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'code');
        $rsm->addScalarResult('description', 'description');
        $rsm->addScalarResult('count', 'count', 'integer');

        $res = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        return $res->getArrayResult();
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
