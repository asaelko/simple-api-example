<?php

namespace AppBundle\EventListener;

use Okvpn\Bundle\DatadogBundle\Client\DogStatsInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Слушаем входящие запросы в попытках определить тип приложения, из которого запросы к нам приходят
 */
class RequestMetricsListener implements EventSubscriberInterface
{
    /** @var DogStatsInterface */
    private $datadogClient;

    public function __construct(
        DogStatsInterface $datadogClient
    ) {
        $this->datadogClient = $datadogClient;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onKernelResponse',
        );
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();
            $apiMethod = $request->getBaseUrl().$request->getPathInfo();

            $this->datadogClient->increment('api.requests', 1, 1.0, [
                'method' => $apiMethod
            ]);

            $requestTiming = (int) ((microtime(true) - $request->server->get('REQUEST_TIME_FLOAT')) * 1000);
            $this->datadogClient->timing('api.timings', $requestTiming, [
                'method' => $apiMethod
            ]);
        }
    }
}
