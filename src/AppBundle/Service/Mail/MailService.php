<?php

namespace AppBundle\Service\Mail;

use AppBundle\Service\AppConfig;
use DateTime;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Класс для работы с почтой через очереди отправки
 */
class MailService
{
    private AppConfig $appConfig;
    private MessageBusInterface $messageBus;

    public function __construct(
        AppConfig $AppConfig,
        MessageBusInterface $messageBus
    )
    {
        $this->appConfig = $AppConfig;
        $this->messageBus = $messageBus;
    }

    /**
     * Отправка сообщения
     *
     * @param Mail $mailMessage
     * @return bool
     */
    public function sendEmail(Mail $mailMessage): bool
    {
        $stamps = [];
        if ($mailMessage->getSendDate()) {
            try {
                $delay = $mailMessage->getSendDate()->getTimestamp() - (new DateTime)->getTimestamp();
                $stamps[] = new DelayStamp($delay * 1000);
            } catch (Exception $e) {
            }
        }

        if (!$this->appConfig->isProd()) {
            $mailMessage->setSubject('[TEST] ' . $mailMessage->getSubject());
        }

        $this->messageBus->dispatch($mailMessage, $stamps);

        return true;
    }
}
