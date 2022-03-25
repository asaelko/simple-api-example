<?php

namespace App\Domain\Infrastructure\AmoCrm\Messenger\Handler;

use App\Domain\Infrastructure\AmoCrm\Messenger\Message\AmoCanceledDriveMessage;
use App\Domain\Infrastructure\AmoCrm\Service\AmoService;
use AppBundle\Service\AppConfig;
use CarlBundle\Repository\DriveRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class AmoCanceledDriveHandler implements MessageHandlerInterface
{
    private DriveRepository $driveRepository;

    private LoggerInterface $logger;

    private AmoService $amoService;
    private AppConfig $appConfig;

    public function __construct(
        DriveRepository $driveRepository,
        LoggerInterface $amoLogger,
        AmoService $amoService,
        AppConfig $appConfig
    )
    {
        $this->amoService = $amoService;
        $this->driveRepository = $driveRepository;
        $this->logger = $amoLogger;
        $this->appConfig = $appConfig;
    }

    public function __invoke(AmoCanceledDriveMessage $message)
    {
        if (!$this->appConfig->isProd()) {
            return;
        }

        $drive = $this->driveRepository->find($message->getDriveId());

        if (!$drive) {
            return;
        }

        $client = $drive->getClient();

        if (!$client) {
            return;
        }

        $contact = $this
            ->amoService
            ->createContact(
                $client->getFullName() ?? '-',
                $client->getPhone(),
                $client->getEmail() ?? "{$client->getId()}@carl-drive.ru"
            );

        if (!$contact) {
            return;
        }

        $this->amoService->createLeadByCanceledDrive($drive, $contact, $message->getCanceledDate());
    }
}