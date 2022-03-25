<?php


namespace App\Domain\Core\Loan\Response;


use CarlBundle\Entity\Loan\LoanProvider;
use OpenApi\Annotations as OA;

class LoanProviderResponse
{
    /**
     * @OA\Property(description="id провайдера в нашей системе")
     */
    public int $id;

    /**
     * @OA\Property(description="Название провайдера")
     */
    public string $title;

    /**
     * @OA\Property(description="Процентная ставка")
     */
    public float $rate;

    /**
     * @OA\Property(description="Описание провайдера")
     */
    public ?string $description;

    /**
     * @OA\Property(description="Дисклеймер если есть")
     */
    public ?string $disclaimer;

    /**
     * @OA\Property(description="Путь до фотографии")
     */
    public ?string $photo;

    /**
     * @OA\Property(description="Минимальный срок кредита")
     */
    public int $minLoanPeriod;

    /**
     * @OA\Property(description="Максимальный срок кредита")
     */
    public int $maxLoanPeriod;

    /**
     * @OA\Property(description="Минимальный платеж")
     */
    public ?int $minAdvancePayment;

    /**
     * @OA\Property(description="Максимальный платеж")
     */
    public ?int $maxAdvancePayment;

    /**
     * @OA\Property(description="Полное название организации если есть")
     */
    public ?string $organizationName;

    public function __construct(LoanProvider $loanProvider)
    {
        $this->id = $loanProvider->getId();
        $this->title = $loanProvider->getTitle();
        $this->rate = $loanProvider->getRate();
        $this->description = $loanProvider->getDescription();
        $this->disclaimer = $loanProvider->getDisclaimer();
        $this->photo = $loanProvider->getPhoto() ? $loanProvider->getPhoto()->getAbsolutePath() : null;
        $this->minLoanPeriod = $loanProvider->getMinLoanPeriod();
        $this->maxLoanPeriod = $loanProvider->getMaxLoanPeriod();
        $this->minAdvancePayment = $loanProvider->getMinAdvancePayment();
        $this->maxAdvancePayment = $loanProvider->getMaxAdvancePayment();
        $this->organizationName = $loanProvider->getFullOrganizationName();
    }
}