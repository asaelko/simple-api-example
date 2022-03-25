<?php

namespace App\Domain\Yandex\TurboApp\Controller\Response;

use CarlBundle\Response\Common\BooleanResponse;

class BookingResultResponse extends BooleanResponse
{
    /**
     * @var int Идентификатор клиента
     */
    public int $clientId;

    /**
     * @var int Идентификатор созданной поездки
     */
    public int $driveId;

    /**
     * BookingResultResponse constructor.
     * @param int $clientId
     * @param int $driveId
     */
    public function __construct(int $clientId, int $driveId)
    {
        parent::__construct(true);

        $this->clientId = $clientId;
        $this->driveId = $driveId;
    }
}
