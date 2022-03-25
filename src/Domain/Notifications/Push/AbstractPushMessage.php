<?php

namespace App\Domain\Notifications\Push;

use CarlBundle\Helpers\TextFormatterHelper;

/**
 * Класс пуш-уведомления
 */
class AbstractPushMessage implements PushMessageInterface
{
    protected ?string $title;
    protected string $text;
    protected array $receivers = [];
    protected array $data = [];
    protected ?string $image = null;
    protected array $context = [];

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
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param $title
     *
     * @return self
     */
    public function setTitle($title): PushMessageInterface
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param $text
     *
     * @return self
     */
    public function setText($text): PushMessageInterface
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return array
     */
    public function getReceivers(): array
    {
        return $this->receivers;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string|null
     */
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

    /**
     * @param array $context
     *
     * @return AbstractPushMessage
     */
    public function setContext(array $context): AbstractPushMessage
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Добавляем имя в текст пуша, если это возможно
     *
     * @param string|null $firstName
     * @param string $text
     * @param bool $keepCase
     * @return string
     */
    protected function addNameToText(?string $firstName, string $text, bool $keepCase = false): string
    {
        if (!$keepCase) {
            $text = TextFormatterHelper::lcfirst($text);
        }

        $prefix = $firstName ? $firstName . ', ' : '';
        $text = $prefix . $text;

        return $keepCase ? $text : TextFormatterHelper::ucfirst($text);
    }
}
