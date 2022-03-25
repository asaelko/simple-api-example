<?php

namespace App\Domain\Marketing\Differture;

use App\Domain\Marketing\Event\UpdateDriveEvent;
use App\Domain\Marketing\Interfaces\MarketingClientInterface;
use CarlBundle\Entity\Drive;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * API-клиент для сервиса differture
 */
class DiffertureClient implements MarketingClientInterface, MessageHandlerInterface
{
    private const API_ENDPOINT = 'http://differture.com/app/_api/collect/custom/';

    private LoggerInterface $logger;

    private ParameterBagInterface $parameterBag;

    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager
    )
    {
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * Позволяет вызывать сервис как напрямую, так и через очередь событий
     * @param UpdateDriveEvent $updateDriveEvent
     */
    public function __invoke(UpdateDriveEvent $updateDriveEvent): void
    {
        $drive = $this->entityManager->getRepository(Drive::class)->find($updateDriveEvent->getDriveId());
        if (!$drive) {
            return;
        }

        $this->updateDrive($drive, $updateDriveEvent->getUpdatedAt());
    }

    /**
     * Обновляет данные в Differture по поездке
     *
     * @param Drive $drive
     * @param DateTime $updatedAt
     */
    public function updateDrive(Drive $drive, DateTime $updatedAt): void
    {
        $data = $this->prepareData($drive, $updatedAt);

        if (!$data) {
            return;
        }

        try {
            $client = new Client(['base_uri' => self::API_ENDPOINT]);
            $response = $client->get($this->parameterBag->get('marketing_dash.differture.project_id'), [
                'query' => $data
            ]);
            $this->logger->info($response->getBody());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Собирает данные для Differture
     * @param Drive $drive
     * @param DateTime $updatedAt
     * @return array
     */
    private function prepareData(Drive $drive, DateTime $updatedAt): array
    {
        $state = null;
        switch ($drive->getState()) {
            case Drive::STATE_NEW:
                $state = 'Новый';
                break;
            case Drive::STATE_FEEDBACK:
            case Drive::STATE_COMPLETED:
                $state = 'Завершенный';
                break;
            case Drive::STATE_CANCELLED:
                $state = 'Отмена';
                break;
        }

        if (!$state) {
            return [];
        }

        return [
            'event_date' => $updatedAt->format('Y-m-d'),
            'userid'     => $drive->getClient()->getId(),
            'event'      => 'Тест-Драйв',
            'timeslot'   => $drive->getSchedule()->getStart()->format('H:i'),
            'status'     => $state,
            'name'       => $drive->getClient()->getFullName(),
            'type'       => $drive->getDriveRate() ? $drive->getDriveRate()->getName() : '-',
            'brand'      => $drive->getCar()->getModel()->getBrand()->getName(),
            'model'      => $drive->getCar()->getModel()->getName(),
            'source'     => $drive->getWidget() ? $drive->getWidget()->getDescription() : 'Приложение',
            'tdid'       => $drive->getId(),
        ];
    }
}
