<?php

namespace App\Domain\Core\Partners\Response;

use App\Entity\PartnersMark;
use OpenApi\Annotations as OA;

class PartnersMarkResponse
{
    /**
     * Идентификатор запроса на оценку
     *
     * @OA\Property(example=374)
     */
    public int $id;

    /**
     * Идентификатор клиента
     *
     * @OA\Property(example="1")
     */
    public int $clientId;

    /**
     * Имя клиента
     *
     * @OA\Property(example="Igor")
     */
    public string $clientName;

    /**
     * Тип запроса клиента
     *
     * @OA\Property(example="Лизинг")
     */
    public string $type;

    /**
     * Оценка клиента
     *
     * @OA\Property(example=10, type="int", nullable=true)
     */
    public ?int $mark;

    /**
     * Коментарий клиента об услуге
     *
     * @OA\Property(example="КАИФ")
     */
    public ?string $comment;

    /**
     * Название партнера
     *
     * @OA\Property(example="Tinkoff")
     */
    public string $partnerName;

    /**
     * Идентификатор партнера в нашей системе
     *
     * @OA\Property(example="1")
     */
    public int $partnerId;

    /**
     * Идентификатор текущей записи
     *
     * @OA\Parameter(example="1")
     */
    public int $partnersMarkId;

    /**
     * Идентификатор конкретного запроса пользователя к партнеру
     *
     * @OA\Property(example="12")
     */
    public string $partnerRequestId;

    public function __construct(PartnersMark $mark, string $partnerName)
    {
        $this->id = $mark->getId();
        $this->clientId = $mark->getClient()->getId();
        $this->clientName = $mark->getClient()->getFullName();
        $this->type = $mark->getRequestType();
        $this->mark = $mark->getMark();
        $this->comment = $mark->getComment();
        $this->partnerName = $partnerName;
        $this->partnerId = $mark->getPartnerId();
        $this->partnersMarkId = $mark->getId();
        $this->partnerRequestId = $mark->getPartnerRequestId();
    }
}
