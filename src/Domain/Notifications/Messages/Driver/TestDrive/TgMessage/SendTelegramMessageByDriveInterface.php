<?php


namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;

interface SendTelegramMessageByDriveInterface
{
    public function getText(): string;

    public function getDriver(): int;
}