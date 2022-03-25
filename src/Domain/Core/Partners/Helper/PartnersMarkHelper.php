<?php


namespace App\Domain\Core\Partners\Helper;


use App\Entity\PartnersMark;
use App\Entity\SubscriptionPartner;
use CarlBundle\Entity\Dealer;
use CarlBundle\Entity\Leasing\LeasingProvider;
use CarlBundle\Entity\Loan\LoanProvider;
use Doctrine\ORM\EntityManagerInterface;

class PartnersMarkHelper
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param PartnersMark $mark
     * @return string
     */
    public function getPartnersName(PartnersMark $mark): string
    {
        $partner = $this->entityManager->getRepository($mark->getPartnerClass())->find($mark->getPartnerId());
        if ($partner instanceof LeasingProvider) {
            $partnerName = $partner->getTitle();
        } elseif ($partner instanceof LoanProvider) {
            $partnerName = $partner->getTitle();
        } elseif ($partner instanceof Dealer) {
            $partnerName = $partner->getName();
        } elseif ($partner instanceof SubscriptionPartner) {
            $partnerName = $partner->getName();
        } else {
            $partnerName = 'CARL';
        }

        return $partnerName;
    }
}
