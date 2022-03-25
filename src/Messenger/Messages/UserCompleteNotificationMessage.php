<?php


namespace App\Messenger\Messages;


class UserCompleteNotificationMessage
{
    private int $id;

    public function __construct(
        int $id
    )
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}