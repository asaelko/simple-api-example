<?php

namespace AppBundle\EventListener;

use AppBundle\Service\AppConfig;
use CarlBundle\Exception\RestException;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use function json_encode;

/**
 * Class ApiExceptionSubscriber
 * @package AppBundle\EventListener
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;
    private AppConfig $appConfig;
    private TranslatorInterface $translator;

    public function __construct(
        LoggerInterface $logger,
        AppConfig $appConfig,
        TranslatorInterface $translator
    )
    {
        $this->logger = $logger;
        $this->appConfig = $appConfig;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * @param ExceptionEvent $event
     * @throws JsonException
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $response = new Response();
        $Ex = $event->getThrowable();

        $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        $status = null;
        $data = [];
        if ($this->appConfig->isProd()) {
            $message = 'Что-то пошло не так. Ошибка будет исправлена в кратчайшие сроки';
        } else {
            $message = $Ex->getMessage();
        }

        switch (true) {
            case $Ex instanceof RestException:
                $code = $Ex->getCode();
                $status = $this->getExceptionClassMessage($Ex);
                $message = $Ex->getMessage();
                $data = $Ex->getData();
                $event->allowCustomResponseCode();
                break;
            case $Ex instanceof NotFoundHttpException:
            case $Ex instanceof MethodNotAllowedHttpException:
                $code = Response::HTTP_NOT_FOUND;
                $message = $Ex->getMessage();
                break;
            case $Ex instanceof NotNormalizableValueException:
                $code = Response::HTTP_NOT_ACCEPTABLE;
                break;
            case $Ex instanceof BadCredentialsException:
            case $Ex instanceof AccessDeniedException:
            case $Ex instanceof AccessDeniedHttpException:
                $code = Response::HTTP_FORBIDDEN;
                $message = $Ex->getMessage();
            break;
        }

        $locale = 'ru_carl';
        if ($this->appConfig->getAppId()) {
            $locale = 'ru_'.$this->appConfig->getAppId();
        }
        $message = $this->translator->trans($message, $data, 'errors', $locale);

        $contentData = ['error' => $message];
        if ($data) {
            $contentData['data'] = $data;
        }

        $response->setStatusCode($code, $status)->setContent(json_encode($contentData, JSON_THROW_ON_ERROR));
        $event->setResponse($response);

        $this->logger->warning($Ex);
    }

    /**
     * Получаем http сообщение из имени исключения
     *
     * @param Throwable $Ex
     *
     * @return string
     */
    private function getExceptionClassMessage(Throwable $Ex): string
    {
        $classPath = explode('\\', get_class($Ex));
        $className = $classPath[count($classPath)-1];

        $className = preg_split('/(?=[A-Z])/', $className);
        $className = array_filter($className);

        $className = implode(' ', $className);
        $className = ucfirst(strtolower($className));

        return $className;
    }
}
