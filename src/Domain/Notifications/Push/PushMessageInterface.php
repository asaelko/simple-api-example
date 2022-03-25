<?php

namespace App\Domain\Notifications\Push;

use App\Domain\Notifications\NotificationInterface;

interface PushMessageInterface extends NotificationInterface
{
    /**
     * Заголовок пуша
     *
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * Текст пуша
     *
     * @return string
     */
    public function getText(): string;

    /**
     * Устанавливает заголовок пуша
     *
     * @param $title
     *
     * @return PushMessageInterface
     */
    public function setTitle($title): PushMessageInterface;

    /**
     * Устанавливает текст пуша
     *
     * @param $text
     *
     * @return PushMessageInterface
     */
    public function setText($text): PushMessageInterface;

    /**
     * Получатели пуша
     *
     * @return array
     */
    public function getReceivers(): array;

    /**
     * Дополнительные данные, отправляемые в пуше
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Ссылка на картинку если есть
     *
     * @return string|null
     */
    public function getImage(): ?string;

    /**
     * Отдает контекст пуша
     *
     * @return array
     */
    public function getContext(): array;
}
