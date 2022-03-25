<?php

namespace App\Domain\Notifications\MassPush;

use App\Domain\Notifications\Push\AbstractPushMessage;
use Ramsey\Uuid\UuidInterface;

/**
 * Класс пуш-уведомления
 */
class MassPushMessage extends AbstractPushMessage
{
    protected ?UuidInterface $uuid;

    public function __construct(
        ?string $title,
        string $text,
        array $receivers,
        array $data = [],
        ?UuidInterface $uuid = null
    )
    {
        $this->title = $title;
        $this->text = $text;
        $this->receivers = $receivers;
        $this->data = $data;
        $this->uuid = $uuid;
    }

    /**
     * Позволяем перезаписывать получателей, чтобы переиспользовать объект для batch-рассылки
     *
     * @param array $receivers
     * @return MassPushMessage
     */
    public function setReceivers(array $receivers): MassPushMessage
    {
        $this->receivers = $receivers;
        return $this;
    }
}
