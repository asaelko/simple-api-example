<?php

namespace App\Domain\Core\Dashboard\Response\Schedule;

use App\Entity\Model\ScheduleNotification;
use CarlBundle\Response\Client\ClientShortResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use RuntimeException;

class ScheduleSubscriptionResponse
{
    /**
     * @OA\Property(example=1)
     */
    public int $id;

    /**
     * @OA\Property(type="object", ref=@Model(type=ClientShortResponse::class))
     */
    public ClientShortResponse $client;

    /**
     * @OA\Property(example="Tesla Model X")
     */
    public string $model;

    /**
     * @OA\Property(example="1618395168")
     */
    public int $requestedTime;

    public function __construct(ScheduleNotification $notification) {
        $notificationId = $notification->getId();
        if (!$notificationId) {
            throw new RuntimeException('Unsupported operation');
        }

        $this->id = $notificationId;
        $this->client = new ClientShortResponse($notification->getClient());
        $model = $notification->getModel();
        if ($model) {
            $this->model = $model->getNameWithBrand();
        }

        $requestedTime = $notification->getRequestedTime();
        if ($requestedTime) {
            $this->requestedTime = $requestedTime->getTimestamp();
        }
    }
}
