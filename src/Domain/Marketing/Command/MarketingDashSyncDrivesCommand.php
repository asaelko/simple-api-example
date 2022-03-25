<?php

namespace App\Domain\Marketing\Command;

use App\Domain\Marketing\Differture\DiffertureClient;
use App\Domain\Marketing\Event\UpdateDriveEvent;
use CarlBundle\Entity\Drive;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use SebastianBergmann\Diff\Diff;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Команда для синхронизации данных с маркетинговым дешем
 */
class MarketingDashSyncDrivesCommand extends Command
{
    protected static $defaultName = 'marketing:sync:drives';

    private EntityManagerInterface $entityManager;
    private DiffertureClient $client;

    public function __construct(
        EntityManagerInterface $entityManager,
        DiffertureClient $client
    )
    {
        parent::__construct(self::$defaultName);

        $this->entityManager = $entityManager;
        $this->client = $client;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send data about drives to special service for drawing')
            ->setHelp('Send data about drives to special service for drawing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->entityManager->getRepository(Drive::class)->getLastMarketingDrives() as $drive) {
            $this->processDrivesEvent($drive);
        }

        return 0;
    }

    /**
     * Обрабатывает изменение состояния поездки по статусам
     *
     * @param Drive $drive
     */
    private function processDrivesEvent(Drive $drive): void
    {
        $logRepository = $this->entityManager->getRepository(LogEntry::class);

        $eventLogs = $logRepository->getLogEntries($drive);

        usort(
            $eventLogs,
            static fn(LogEntry $logEntryA, LogEntry $logEntryB) => $logEntryA->getVersion() <=> $logEntryB->getVersion()
        );

        $lastState = null;
        foreach ($eventLogs as $eventLogEntry) {
            assert($eventLogEntry instanceof LogEntry);

            $loggedState = $eventLogEntry->getData()['state'] ?? $lastState;

            if ($lastState !== $loggedState && in_array($loggedState, [0, 5, 6, 7], true)) {
                $versionedDrive = clone $drive;
                $logRepository->revert($versionedDrive, $eventLogEntry->getVersion());
                $this->client->updateDrive($versionedDrive, $eventLogEntry->getLoggedAt());

                $lastState = $loggedState;
            }
        }
    }
}
