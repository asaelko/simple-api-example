<?php
declare(strict_types=1);

namespace App\Domain\Core\Dashboard\EventSubscriber;

use App\Domain\Core\Dashboard\Events\WidgetWithDrivesFilterEvent;
use DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DashboardFilterEventSubscriber implements EventSubscriberInterface
{

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WidgetWithDrivesFilterEvent::WIDGET_WITH_DRIVES_FILTER_EVENT => 'onFilterByParams',
        ];
    }

    public function onFilterByParams(WidgetWithDrivesFilterEvent $event)
    {
        $request = $this->requestStack->getMasterRequest();
        $event->setDateStart($request->query->has('dateStart') ? new DateTime($request->query->get('dateStart')) : null);
        $event->setDateEnd($request->query->has('dateEnd') ? new DateTime($request->query->get('dateEnd')) : null);
    }
}