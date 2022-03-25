<?php


namespace App\Domain\Core\ExperienceCenters\Service;


use App\Domain\Core\ExperienceCenters\Request\AdminCreateCenterRequest;
use App\Domain\Core\ExperienceCenters\Request\AdminCreateScheduleForCenter;
use App\Entity\ExperienceCenter;
use App\Entity\ExperienceCenterSchedule;
use CarlBundle\Entity\Brand;
use Doctrine\ORM\EntityManagerInterface;

class ExperienceCenterBrandService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Создает центр
     * @param Brand $brand
     * @param AdminCreateCenterRequest $request
     */
    public function createCenter(Brand $brand, AdminCreateCenterRequest $request): ExperienceCenter
    {
        $experienceCenter = new ExperienceCenter();
        $experienceCenter->setBrand($brand);
        $experienceCenter->setEmailToSendRequest($request->email);
        $experienceCenter->setName($request->name);
        $experienceCenter->setDescription($request->description);
        $experienceCenter->setShortDescription($request->shortDescription);
        $experienceCenter->setFullOrganizationName($request->organizationName);

        $this->entityManager->persist($experienceCenter);
        $this->entityManager->flush();
        return $experienceCenter;
    }

    public function createSlot(ExperienceCenter $center, AdminCreateScheduleForCenter $request): void
    {
        $slot = new ExperienceCenterSchedule();
        $slot->setStart($request->start);
        $slot->setEnd($request->start + ($request->duration * 60));
        $slot->setPrice($request->price);
        $slot->setIsBooked(false);
        $slot->setExperienceCenter($center);

        $this->entityManager->persist($slot);
        $this->entityManager->flush();
    }
}