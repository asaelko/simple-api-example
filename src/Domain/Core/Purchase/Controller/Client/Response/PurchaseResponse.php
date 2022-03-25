<?php

namespace App\Domain\Core\Purchase\Controller\Client\Response;

use App\Entity\Purchase\Purchase;
use CarlBundle\Entity\Photo;
use DateTime;
use OpenApi\Annotations as OA;

/**
 * Презентер заявки о покупке для клиента
 */
class PurchaseResponse
{
    private const DECISIONS = [
        Purchase::RECEIPT_NEW => 'Рассматривается',
        Purchase::RECEIPT_DECLINED => 'Отклонена',
        Purchase::RECEIPT_ACCEPTED => 'Одобрена',
        Purchase::RECEIPT_CARD_ISSUED => 'Подарки выданы',
    ];

    /** @var string */
    public $id;

    /** @var string */
    public $model;

    /** @var DateTime */
    public $requestedAt;

    /** @var Photo */
    public $receiptPhoto;

    /** @var int */
    public $receiptDecision;

    /** @var string */
    public $receiptDecisionText;

    /** @var string|null */
    public $declineReason;

    /** @var DateTime|null */
    public $decidedAt;

    /** @var bool */
    public $isFuelCardIssued;

    /**
     * @OA\Property(
     *              property="state",
     *              type="object",
     *              description="Статус заявки",
     *                  @OA\Property(
     *                      property="decision",
     *                      description="Код статуса заявки",
     *                      type="integer",
     *                      example="0",
     *                      nullable=true
     *                  ),
     *                  @OA\Property(
     *                      property="header",
     *                      description="Текст для отображения на экране",
     *                      type="string",
     *                      example="Купили авто с помощью CARL?"
     *                  ),
     *                  @OA\Property(
     *                      property="text",
     *                      description="Текст для отображения на экране",
     *                      type="string",
     *                      example="Заберите ваши подарки тут!"
     *                  )
     * )
     */
    public array $state = [];

    public function __construct(Purchase $purchase)
    {
        $this->id = $purchase->getId()->getHex();
        $this->requestedAt = $purchase->getRequestedAt()->getTimestamp();
        $this->receiptPhoto = $purchase->getReceiptPhoto();
        $this->receiptDecision = $purchase->getReceiptDecision();
        $this->receiptDecisionText = self::DECISIONS[$purchase->getReceiptDecision()];
        $this->declineReason = $purchase->getDeclineReason();
        $this->decidedAt = $purchase->getDecidedAt() ? $purchase->getDecidedAt()->getTimestamp() : null;
        $this->isFuelCardIssued = $purchase->isFuelCardIssued();
        $this->model = $purchase->getModel()->getNameWithBrand();

        switch ($purchase->getReceiptDecision()) {
            case Purchase::RECEIPT_NEW:
                $this->state['header'] = 'Проверяем ваши документы';
                $this->state['text'] = 'Ждите звонка, скоро свяжемся!';
                break;
            case Purchase::RECEIPT_DECLINED:
                $this->state['header'] = 'Возникли проблемы';
                $this->state['text'] = 'Ваш чек не подходит, загрузите новый';
                break;
            case Purchase::RECEIPT_ACCEPTED:
                $this->state['header'] = 'Документы проверены';
                $this->state['text'] = 'Мы готовы передать вам ваши подарки';
                break;
        }
    }
}
