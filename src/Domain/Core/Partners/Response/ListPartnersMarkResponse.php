<?php

namespace App\Domain\Core\Partners\Response;

use App\Domain\Core\Partners\Helper\PartnersMarkHelper;
use App\Entity\PartnersMark;
use OpenApi\Annotations as OA;

class ListPartnersMarkResponse
{
    /**
     * Идентификатор оценки
     *
     * @OA\Property(example="1")
     */
    public int $id;

    /**
     * Оценка
     *
     * @OA\Property(example="10", type="int", nullable=true)
     */
    public ?int $mark;

    /**
     * UnixTimestamp времени создания записи
     *
     * @OA\Property(example=1618391568)
     */
    public int $dateCreate;

    /**
     * UnixTimestamp времени оценки пользователя
     *
     * @OA\Property(example=1618395168, type="int")
     */
    public ?int $dateUpdate;

    /**
     * Комментарий пользователя
     *
     * @OA\Property(example="КАИФ")
     */
    public ?string $comment;

    /**
     * Тип события, требующего оценки
     *
     * @OA\Property(example="Кредит")
     */
    public string $type;

    /**
     * Идентификатор партнера
     *
     * @OA\Property(example="1")
     */
    public int $partnerId;

    /**
     * Наименование партнера
     *
     * @OA\Property(example="Тинькофф")
     */
    public string $partnerName;

    /**
     * Идентификатор клиента
     *
     * @OA\Property(example="1")
     */
    public int $clientId;

    /**
     * Имя клиента
     *
     * @OA\Property(example="Иван Иванов")
     */
    public string $clientName;

    public function __construct(
        PartnersMark $mark,
        string $partnerName
    )
    {
        $this->id = $mark->getId();
        $this->mark = $mark->getMark();
        $this->dateCreate = $mark->getDateCreate()->getTimestamp();
        $this->dateUpdate = $mark->getDateUpdate() ? $mark->getDateUpdate()->getTimestamp() : null;
        $this->comment = $mark->getComment();
        $this->type = $mark->getRequestType();
        $this->partnerId = $mark->getPartnerId();
        $this->clientId = $mark->getClient()->getId();
        $this->partnerName = $partnerName;
        $this->clientName = $mark->getClient()->getFullName();
    }
}
