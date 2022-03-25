<?php


namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;


use CarlBundle\Helpers\TextFormatterHelper;

abstract class AbstractDriverTelegramMessage implements SendTelegramMessageByDriveInterface
{
    protected string $text;

    protected int $driver;

    public function getText(): string
    {
        return $this->text;
    }

    public function getDriver(): int
    {
        return $this->driver;
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