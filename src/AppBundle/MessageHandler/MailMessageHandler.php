<?php

namespace AppBundle\MessageHandler;

use AppBundle\Service\Mail\Mail;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SparkPost\SparkPost;
use SparkPost\SparkPostException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailMessageHandler implements MessageHandlerInterface
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    /**
     * @param MailerInterface $mailer
     * @param LoggerInterface $mailLogger
     */
    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $mailLogger
    )
    {
        $this->logger = $mailLogger;
        $this->mailer = $mailer;
    }

    public function __invoke(Mail $mail)
    {
        $mailContent = json_encode($mail);

        try {
            $email = (new Email())
                ->from(new Address($mail->getSenderEmail(), $mail->getSenderName()))
                ->subject($mail->getSubject())
                ->text($mail->getTextContent())
                ->html($mail->getHtmlContent());

            foreach($mail->getRecipients() as $recipient) {
                $email->to(new Address($recipient['email'], $recipient['name'] ?? ''));
            }

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->critical($e->getMessage());
            $this->logger->critical($e->getDebug());
            $this->logger->debug($mailContent);
            throw $e;
        }

        $this->logger->info('Mail was sent: ' . $mailContent);
    }
}
