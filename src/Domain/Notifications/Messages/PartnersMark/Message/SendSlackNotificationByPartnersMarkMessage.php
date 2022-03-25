<?php


namespace App\Domain\Notifications\Messages\PartnersMark\Message;


use App\Entity\PartnersMark;

class SendSlackNotificationByPartnersMarkMessage
{
    private int $partnersMarkId;

    public function __construct(PartnersMark $mark)
    {
        $this->partnersMarkId = $mark->getId();
    }

    public function getMarkId(): int
    {
        return $this->partnersMarkId;
    }
}