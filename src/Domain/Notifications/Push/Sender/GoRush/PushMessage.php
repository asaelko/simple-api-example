<?php

namespace App\Domain\Notifications\Push\Sender\GoRush;

use Ramsey\Uuid\UuidInterface;

/**
 * Пуш-сообщение, отправляемое через GoRush
 */
class PushMessage
{
    private ?string $title;

    private string $body;

    private array $receivers;

    private array $data;

    private ?string $image;

    private array $context;

    private ?UuidInterface $uuid;

    public function __construct(
        ?string $title,
        string $body,
        array $receivers,
        array $data = [],
        ?UuidInterface $uuid = null,
        ?string $image = null,
        array $context = []
    )
    {
        $this->title = $title;
        $this->body = $body;
        $this->receivers = $receivers;
        $this->data = $data;
        $this->uuid = $uuid;
        $this->image = $image;
        $this->context = $context;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getReceivers(): array
    {
        return $this->receivers;
    }

    /**
     * @param array $receivers
     * @return PushMessage
     */
    public function setReceivers(array $receivers): PushMessage
    {
        $this->receivers = $receivers;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return UuidInterface|null
     */
    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @param UuidInterface|null $uuid
     * @return PushMessage
     */
    public function setUuid(?UuidInterface $uuid): PushMessage
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
