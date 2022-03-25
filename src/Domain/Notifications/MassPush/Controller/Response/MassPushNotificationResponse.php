<?php

namespace App\Domain\Notifications\MassPush\Controller\Response;

use App\Entity\Notifications\MassPush\MassPushNotification;
use OpenApi\Annotations as OA;

/**
 * Ответ в виде объекта масспуша для отображения в деше
 */
class MassPushNotificationResponse
{
    /** @OA\Property(example=3) */
    public int $id;

    /** @OA\Property(example="CARL") */
    public ?string $title;

    /** @OA\Property(example="Текст масс-пуш рассылки") */
    public string $text;

    /** @OA\Property(example=1612106356) */
    public int $sendDate;

    /** @OA\Property(example="https://carl-drive.ru/mycar") */
    public ?string $link;

    /** @OA\Property(example=13705) */
    public int $processedClients;

    /** @OA\Property(example=1612106376) */
    public ?int $finishDate;

    /** @OA\Property(example=null) */
    public ?int $cancelDate;

    public function __construct(MassPushNotification $notification)
    {
        $this->id = $notification->getId();
        $this->title = $notification->getTitle();
        $this->text = $notification->getText();
        $this->link = $notification->getLink();
        $this->sendDate = $notification->getSendDate()->getTimestamp();
        $this->processedClients = $notification->getProcessedClients();
        $this->finishDate = $notification->getFinishDate() ? $notification->getFinishDate()->getTimestamp() : null;
        $this->cancelDate = $notification->getCancelDate() ? $notification->getCancelDate()->getTimestamp() : null;
    }
}
