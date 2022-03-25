<?php

namespace App\Domain\Notifications\MassPush;

use App\Domain\Notifications\MassPush\Controller\Request\NewMassPushRequest;
use App\Domain\Notifications\MassPush\Event\MassPushMessageEvent;
use App\Domain\Notifications\MassPush\Exception\NonUniqueMassPushException;
use App\Domain\Notifications\MassPush\Exception\TooLateToCancelMassPushException;
use App\Domain\Notifications\Push\Sender\PushSenderInterface;
use App\Entity\Notifications\MassPush\MassPushNotification;
use CarlBundle\Entity\Client;
use CarlBundle\Repository\ClientRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Generator;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Predis\Client as PredisClient;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Менеджер управления масспушами
 */
class MassPushManager
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private ClientRepository $clientRepository;
    private PredisClient $redisStorage;
    private PushSenderInterface $pushSender;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
        ClientRepository $clientRepository,
        PredisClient $redisStorage,
        PushSenderInterface $pushSender
    )
    {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->clientRepository = $clientRepository;
        $this->redisStorage = $redisStorage;
        $this->pushSender = $pushSender;
    }

    /**
     * Создает новую масспуш-рассылку
     *
     * @param NewMassPushRequest $massPushRequest
     * @return MassPushNotification
     * @throws NonUniqueMassPushException
     */
    public function create(NewMassPushRequest $massPushRequest): MassPushNotification
    {
        $similarMassPushes = $this->entityManager->getRepository(MassPushNotification::class)
            ->searchMassPushesByDate($massPushRequest->getSendDate());

        if ($similarMassPushes) {
            throw new NonUniqueMassPushException('В этот день уже есть рассылка масспушей');
        }

        // добавляем запись о масспуше в базу
        $massPushNotification = new MassPushNotification();
        $massPushNotification
            ->setTitle($massPushRequest->getTitle())
            ->setText($massPushRequest->getText())
            ->setLink($massPushRequest->getLink())
            ->setSendDate($massPushRequest->getSendDate() ?? new DateTime());

        $this->entityManager->persist($massPushNotification);
        $this->entityManager->flush();

        $delay = $massPushNotification->getSendDate()->getTimestamp() - time();
        $this->messageBus->dispatch(
            new MassPushMessageEvent($massPushNotification),
            [new DelayStamp($delay * 1000)]
        );

        return $massPushNotification;
    }

    /**
     * Редактирует масспуш-рассылку
     *
     * @param MassPushNotification $massPushNotification
     * @param NewMassPushRequest $massPushRequest
     * @return MassPushNotification
     * @throws TooLateToCancelMassPushException
     * @throws NonUniqueMassPushException
     */
    public function edit(MassPushNotification $massPushNotification, NewMassPushRequest $massPushRequest): MassPushNotification
    {
        if ($massPushNotification->getSendDate()->getTimestamp() < time() + 30) {
            throw new TooLateToCancelMassPushException('Слишком поздно редактировать масспуш');
        }

        $similarMassPushes = $this->entityManager->getRepository(MassPushNotification::class)->findBy([
            'sendDate' => $massPushRequest->getSendDate()->format('Y-m-d')
        ]);

        if ($similarMassPushes) {
            throw new NonUniqueMassPushException('В этот день уже есть рассылка масспушей');
        }

        $massPushNotification
            ->setTitle($massPushRequest->getTitle())
            ->setText($massPushRequest->getText())
            ->setLink($massPushRequest->getLink())
            ->setSendDate($massPushRequest->getSendDate() ?? new DateTime());

        $this->entityManager->flush();

        $delay = $massPushNotification->getSendDate()->getTimestamp() - time();
        $this->messageBus->dispatch(
            new MassPushMessageEvent($massPushNotification),
            [new DelayStamp($delay * 1000)]
        );

        return $massPushNotification;
    }

    /**
     * Отменяет рассылку масспуша
     *
     * @param MassPushNotification $massPushNotification
     * @return MassPushNotification
     */
    public function cancel(MassPushNotification $massPushNotification): MassPushNotification
    {
        $massPushNotification->setCancelDate(new DateTime());
        $this->entityManager->flush();

        return $massPushNotification;
    }


    /**
     * @param MassPushNotification $massPushNotification
     * @return bool
     * @throws Exception
     */
    public function send(MassPushNotification $massPushNotification): bool
    {
        // запрашиваем лок на рассылку масспуша
        $massPushLock = $this->getLockFactory()->createLock($massPushNotification->getId(), 300);
        if (!$massPushLock->acquire()) {
            return false;
        }

        $idsBatch = [];

        $uuid = Uuid::uuid4();

        // создаем новое сообщение масспуша
        $pushMessage = new MassPushMessage(
            $massPushNotification->getTitle(),
            $massPushNotification->getText(),
            [],
            ['url' => $massPushNotification->getLink()],
            $uuid
        );

        foreach ($this->getRecipients() as $recipient) {
            $idsBatch[] = $recipient->getId();

            // ограничение в конфигах вообще стоит в 100, но берем с запасом
            if (count($idsBatch) < 80) {
                continue;
            }

            $pushMessage->setReceivers([Client::class => $idsBatch]);
            $this->pushSender->processPush($pushMessage);
            $idsBatch = [];

            // обновляем данные в БД
            $massPushNotification->incrementProcessedClients(count($idsBatch));

            // проверяем, не отменена ли рассылка
            if ($massPushNotification->getCancelDate()) {
                $massPushNotification->setFinishDate(new DateTime());
                break;
            }
            $this->entityManager->flush();
        }

        if ($idsBatch) {
            $pushMessage->setReceivers([Client::class => $idsBatch]);
            $this->pushSender->processPush($pushMessage);
        }

        $massPushNotification->incrementProcessedClients(count($idsBatch));
        $massPushNotification->setFinishDate(new DateTime());
        $this->entityManager->flush();

        $massPushLock->release();
        return true;
    }

    /**
     * Достаем из базы получателей письма
     *
     * @return Generator|Client[]
     */
    private function getRecipients(): Generator
    {
        $QueryBuilder = $this->entityManager->createQueryBuilder();
        $predicates = $QueryBuilder->expr()->andX(
            $QueryBuilder->expr()->isNotNull('client.pushToken'),
            $QueryBuilder->expr()->isNull('client.deletedAt'),
            $QueryBuilder->expr()->isNull('client.appTag')
        );

        return $this->clientRepository->iterate($predicates);
    }

    /**
     * Фабрика локов
     *
     * @return LockFactory
     */
    private function getLockFactory(): LockFactory
    {
        $store = new RedisStore($this->redisStorage);
        return new LockFactory($store);
    }
}
