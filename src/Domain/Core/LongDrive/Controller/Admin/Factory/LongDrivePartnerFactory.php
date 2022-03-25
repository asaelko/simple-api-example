<?php

namespace App\Domain\Core\LongDrive\Controller\Admin\Factory;

use App\Domain\Core\LongDrive\Controller\Admin\Request\CreateLongDrivePartnerRequest;
use App\Entity\LongDrive\LongDrivePartner;
use CarlBundle\Entity\Media\Media;
use CarlBundle\Entity\Photo;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;

class LongDrivePartnerFactory
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function fillPartner(LongDrivePartner $partner, CreateLongDrivePartnerRequest $request): LongDrivePartner
    {
        /** @var Media $photo */
        $photo = $this->entityManager->getRepository(Media::class)->find($request->logoId);
        if (!$photo) {
            throw new InvalidValueException('Не указан логотип партнера');
        }

        $partner->setName($request->partnerName)
            ->setDescription($request->description)
            ->setShortDescription($request->shortDescription)
            ->setFullOrganizationName($request->fullOrganizationName)
            ->setEmail($request->email)
            ->setLogo($photo);

        return $partner;
    }
}
