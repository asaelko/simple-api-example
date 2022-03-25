<?php

namespace AppBundle\Service\Mail;

use DateTime;
use JsonSerializable;

/**
 * Класс почтового сообщения
 */
class Mail implements JsonSerializable
{
    /** @var array */
    private $recipients;

    /** @var string */
    private $subject;

    /** @var string|null */
    private $htmlContent;

    /** @var string|null */
    private $textContent;

    /** @var string|null */
    private $senderName;

    /** @var string|null */
    private $senderEmail;

    /** @var DateTime|null */
    private $sendDate;

    /** @var array|null  */
    private ?array $attachments = null;

    public function __construct()
    {
        $this->recipients = [];
        $this->senderName = 'CARL';
        $this->senderEmail = 'notifications@carl-drive.ru';
    }

    /**
     * @return array
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @param array $recipients
     * @return Mail
     */
    public function setRecipients(array $recipients): Mail
    {
        $this->recipients = $recipients;
        return $this;
    }

    /**
     * Добавляем файл для атача в формате
     * [
     *   [
     *     'name' => $fileName,
     *     'type' => $fileType,
     *     'data' => $fileData,
     *   ],
     * ],
     * @param array|null $attachments
     */
    public function setAttachments(?array $attachments)
    {
        $this->attachments = $attachments;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    /**
     * Добавляем получателя в список получателей данного почтового сообщения
     * Формат ['name' => 'User Name', 'email' => 'user@example.com']
     *
     * @param array $recipient
     * @return Mail
     */
    public function addRecipient(array $recipient): Mail
    {
        $this->recipients[] = $recipient;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return Mail
     */
    public function setSubject(string $subject): Mail
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHtmlContent(): ?string
    {
        return $this->htmlContent;
    }

    /**
     * @param string|null $htmlContent
     * @return Mail
     */
    public function setHtmlContent(?string $htmlContent): Mail
    {
        $this->htmlContent = $htmlContent;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTextContent(): ?string
    {
        return $this->textContent;
    }

    /**
     * @param string|null $textContent
     * @return Mail
     */
    public function setTextContent(?string $textContent): Mail
    {
        $this->textContent = $textContent;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    /**
     * @param string|null $senderName
     * @return Mail
     */
    public function setSenderName(?string $senderName): Mail
    {
        $this->senderName = $senderName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    /**
     * @param string|null $senderEmail
     * @return Mail
     */
    public function setSenderEmail(?string $senderEmail): Mail
    {
        $this->senderEmail = $senderEmail;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getSendDate(): ?DateTime
    {
        return $this->sendDate;
    }

    /**
     * @param DateTime|null $sendDate
     * @return Mail
     */
    public function setSendDate(?DateTime $sendDate): Mail
    {
        $this->sendDate = $sendDate;
        return $this;
    }

    /**
     * Сериализуем сообщение в json в заданном формате
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $serializedMail = [
            'content' => [
                'subject'     => $this->getSubject(),
                'html'        => $this->getHtmlContent(),
                'text'        => $this->getTextContent(),
                'from'      => [
                    'email' => $this->getSenderEmail(),
                    'name'  => $this->getSenderName(),
                ],
            ],
            'recipients'  => [],
        ];

        if ($this->attachments) {
            $serializedMail['content']['attachments'] = $this->attachments;
        }

        foreach ($this->getRecipients() as $recipient) {
            $serializedMail['recipients'][] = [
                'address' => $recipient
            ];
        }

        return $serializedMail;
    }
}
