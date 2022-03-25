<?php

namespace UnitTest\Domain\Core\Client\Service;

use App\Domain\Core\Client\Controller\Request\SendEmailVerificationRequest;
use App\Domain\Core\Client\Repository\EmailVerificationRepository;
use App\Domain\Core\Client\Service\EmailVerificationService;
use AppBundle\Service\AppConfig;
use AppBundle\Service\Mail\MailService;
use CarlBundle\Entity\Client;
use Codeception\Test\Unit;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Twig\Environment as TwigEnvironment;

class EmailVerificationServiceTest extends Unit
{
    /**
     * Проверяем корректный запрос на смену почты для клиента
     */
    public function testProcessVerificationRequest()
    {
        $emailVerificationService = $this->make(EmailVerificationService::class, [
            'emailVerificationRepository' => $this->make(EmailVerificationRepository::class, [
                'getLastVerificationRequest' => null
            ]),
            'entityManager' => $this->makeEmpty(EntityManagerInterface::class, [
                'getRepository' => $this->makeEmpty(EntityRepository::class, [
                    'checkClientUniqueness' => []
                ])
            ]),
            'twigEngine' => $this->makeEmpty(TwigEnvironment::class),
            'mailService' => $this->makeEmpty(MailService::class),
            'appConfig' => $this->make(AppConfig::class, [
                'getWlConfig' => ['mail' => ['sender' => ['name' => '', 'mail' => '']]]
            ]),
        ]);

        $emailVerificationRequest = new SendEmailVerificationRequest();
        $emailVerificationRequest->email = 'new@carl-drive.ru';

        $client = $this->make(Client::class, [
            'email' => 'old@carl-drive.ru',
            'verifiedAt' => new DateTime(),
            'getUnfinishedDrives' => $this->make(ArrayCollection::class, [
                'count' => 0
            ])
        ]);

        $emailVerificationService->processVerificationRequest($emailVerificationRequest, $client);

        self::assertEquals('new@carl-drive.ru', $client->getEmail());

    }
}
