<?php
declare(strict_types=1);

namespace App\Domain\Core\Dashboard\Events;

use DateTime;
use Symfony\Contracts\EventDispatcher\Event;

class WidgetWithDrivesFilterEvent extends Event
{

    public const WIDGET_WITH_DRIVES_FILTER_EVENT = 'widget.drives.with.filter.event';

    private ?DateTime $dateStart = null;
    private ?DateTime $dateEnd = null;

    /**
     * @return DateTime|null
     */
    public function getDateStart(): ?DateTime
    {
        return $this->dateStart;
    }

    /**
     * @param DateTime|null $dateStart
     */
    public function setDateStart(?DateTime $dateStart): void
    {
        $this->dateStart = $dateStart;
    }

    /**
     * @return DateTime
     */
    public function getDateEnd(): ?DateTime
    {
        return $this->dateEnd;
    }

    /**
     * @param DateTime|null $dateEnd
     */
    public function setDateEnd(?DateTime $dateEnd): void
    {
        $this->dateEnd = $dateEnd;
    }

}