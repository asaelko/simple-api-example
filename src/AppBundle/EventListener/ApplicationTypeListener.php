<?php

namespace AppBundle\EventListener;

use AppBundle\Service\AppConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Слушаем входящие запросы в попытках определить тип приложения, из которого запросы к нам приходят
 */
class ApplicationTypeListener implements EventSubscriberInterface
{
    /**
     * @var AppConfig
     */
    private $appConfig;

    public function __construct(
        AppConfig $appConfig
    ) {
        $this->appConfig = $appConfig;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onKernelRequest',
        );
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if ($event->getRequest()->headers->get('content-type', '') === '') {
            $event->getRequest()->headers->set('content-type', 'application/json');
        }

        $appId = $event->getRequest()->headers->get('x-application-id');
        $appId = mb_strtolower($appId);

        if (!$appId) {
            return;
        }

        if ($appId === AppConfig::WL_MAIN) {
            return;
        }

        if (in_array($appId, $this->appConfig->getWlTypes(), true)) {
            $this->appConfig->setAppId($appId);
        }
    }
}
