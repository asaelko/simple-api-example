<?php


namespace App\Domain\Core\Leasing\Response;

use OpenApi\Annotations as OA;

class LeasingResponse
{
    /**
     * @OA\Property(description="Ежемесячный платеж по лизингу")
     */
    public float $monthPay;

    /**
     * @OA\Property(description="Общая стоимость")
     */
    public float $totalPayment;

    /**
     * @OA\Property(description="Сумма налогооблажения")
     */
    public float $nds;

    /**
     * @OA\Property(description="Уменьшение стоимости с от уменьшения налога на прибыль по доход - расход")
     */
    public float $decreaseCostsOfIncomeTax;

    /**
     * @OA\Property(description="id лизинг провайдера")
     */
    public int $leasingProviderId;

    public function __construct(
        float $monthPay,
        float $totalPayment,
        float $nds,
        float $decreaseCostsOfIncomeTax,
        int $leasingProviderId
    )
    {
        $this->monthPay = $monthPay;
        $this->totalPayment = $totalPayment;
        $this->nds = $nds;
        $this->decreaseCostsOfIncomeTax = $decreaseCostsOfIncomeTax;
        $this->leasingProviderId = $leasingProviderId;
    }
}