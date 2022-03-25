<?php

namespace AppBundle\Service\Slack;

use DateTime;
use JsonSerializable;

/**
 * Класс сообщения в slack
 */
class SlackMessage implements JsonSerializable
{
    /** @var string имя слак канала */
    private $receiver;

    /** @var string */
    private $message;

    /** @var DateTime|null */
    private $sendDate;

    /**
     * @param string $receiver
     * @param string $message
     * @param DateTime|null $sendDate
     */
    public function __construct(string $receiver, string $message, ?DateTime $sendDate = null)
    {
        $this->receiver = $receiver;
        $this->message = $message;
        $this->sendDate = $sendDate;
    }

    /**
     * @return string
     */
    public function getReceiver(): string
    {
        return $this->receiver;
    }

    /**
     * @param string $receiver
     * @return self
     */
    public function setReceiver(string $receiver): self
    {
        $this->receiver = $receiver;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
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
     * @return self
     */
    public function setSendDate(?DateTime $sendDate): self
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
        $serializedSlackMessage = [
            'receiver' => $this->getReceiver(),
            'message' => $this->getMessage(),
        ];

        return $serializedSlackMessage;
    }
}
