<?php


namespace App\Domain\ExperienceCenters\Service;


use App\Domain\Core\ExperienceCenters\Service\ExperienceCenterBookService;
use App\Domain\Core\ExperienceCenters\Service\ExperienceCenterNotificationService;
use App\Entity\ExperienceCenterSchedule;
use App\Entity\ExperienceRequest;
use CarlBundle\Entity\Client;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManager;

class ExperienceCenterBookServiceTest extends Unit
{
    public function testCreateRequest()
    {
        /** @var ExperienceCenterBookService $experienceCenterBookService */
        $experienceCenterBookService = $this->make(ExperienceCenterBookService::class);

        $client = $this->make(Client::class, ['name' => 'Ivan', 'phone' => '7989898989']);
        $slot = $this->make(ExperienceCenterSchedule::class);

        $request = $experienceCenterBookService->createRequest($client, $slot);
        $this->assertInstanceOf(ExperienceRequest::class, $request);
    }

    public function testDeclineFreeExperienceRequest()
    {
        /** @var ExperienceCenterBookService $experienceCenterBookService */
        $experienceCenterBookService = $this->make(
            ExperienceCenterBookService::class,
            [
                'entityManager' => $this->make(EntityManager::class, [
                    'flush' => Expected::once(),
                    'persist' => Expected::atLeastOnce(),
                ]),
                'notificationService' => $this->make(
                    ExperienceCenterNotificationService::class,
                    [
                        'notifyClient' => Expected::once()
                    ]
                )
            ]
        );

        /** @var ExperienceRequest $experienceRequest */
        $experienceRequest = $this->make(
            ExperienceRequest::class,
            [
                'state' => ExperienceRequest::STATE_NEW, '',
                'paymentId' => null,
                'scheduleSlot' => new ExperienceCenterSchedule()
            ]
        );

        $result = $experienceCenterBookService->declineRequestByBrand($experienceRequest);
        $this->assertEquals(true, $result);
        $this->assertEquals($experienceRequest->getState(), ExperienceRequest::STATE_DECLINE);
    }
}