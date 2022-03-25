<?php


namespace App\Domain\Notifications\Push;


use App\Domain\Core\Partners\Helper\PartnersMarkHelper;
use App\Entity\PartnersMark;
use CarlBundle\Entity\Dealer;
use CarlBundle\Entity\Leasing\LeasingProvider;
use CarlBundle\Entity\Loan\LoanProvider;
use DealerBundle\Entity\DriveOffer;
use Doctrine\ORM\EntityManagerInterface;

class PartnersPushMessage extends AbstractPushMessage
{
    private PartnersMark $mark;

    public function __construct(PartnersMark $mark, PartnersMarkHelper $helper)
    {
        $this->mark = $mark;
        $partnerName = $helper->getPartnersName($mark);

        $text = "вы запрашивали {$mark->getRequestType()} в {$partnerName}. Как все прошло? ★★★★★";
        $this->text = $this->addNameToText($mark->getClient()->getFullName(), $text);

        $this->title = "Оставьте отзыв";
        $this->receivers = [$mark->getClient()];
        $this->data = [];
    }

    public function checkOfferNotBook(EntityManagerInterface $entityManager): bool
    {
        if ($this->mark->getRequestType() != PartnersMark::TYPE_DRIVE_OFFER) {
            return true;
        }
        $partner = $entityManager->getRepository($this->mark->getPartnerClass())->find($this->mark->getId());

        if (!($partner instanceof Dealer)) {
            return false;
        }

        $offer = $entityManager->getRepository($this->mark->getPartnerRequestClass())->find($this->mark->getPartnerRequestId());
        if (!($offer instanceof DriveOffer)) {
            return false;
        }

        if ($this->mark->getRequestType() == PartnersMark::TYPE_DRIVE_OFFER && $offer->getBookTransactionStatus()) {
            return false;
        }
        return true;
    }
}