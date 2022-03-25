<?php

namespace App\Domain\Core\Client\Service;

use AppBundle\Service\AppConfig;
use AppBundle\Service\TokenGenerator;
use CarlBundle\Entity\Client;
use CarlBundle\Exception\ClientIsBanLoginException;
use CarlBundle\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Сервис авторизации клиентов
 *
 * Любые попытки аутентифицировать и авторизовать клиента должны идти через этот класс
 */
class ClientAuthService
{
    private ClientRepository $clientRepository;
    private AppConfig $appConfig;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ClientRepository $clientRepository,
        AppConfig $appConfig,
        EntityManagerInterface $entityManager
    )
    {
        $this->clientRepository = $clientRepository;
        $this->appConfig = $appConfig;
        $this->entityManager = $entityManager;
    }

    /**
     * Пытается найти пользователя в системе
     *
     * @param array $data
     * @return Client|null
     * @throws ClientIsBanLoginException
     */
    public function tryAuthBy(array $data): ?Client
    {
        $data['deletedAt'] ??= null;
        $data['appTag'] ??= null;
        if (!$data['appTag'] && $this->appConfig->getAppId()) {
            $data['appTag'] = $this->appConfig->getAppId();
        }

        $client = $this->clientRepository->findOneBy($data);
        if (!$client) {
            return null;
        }

        if ($client->isBanLogin()) {
            throw new ClientIsBanLoginException('Клиент заблокирован в сервисе');
        }

        $this->updateToken($client);

        return $client;
    }

    /**
     * Обновляем авторизационный токен пользователя
     *
     * @param Client $client
     * @return Client
     */
    public function updateToken(Client $client): Client
    {
        $token = TokenGenerator::getGUID();
        $client->setToken($token);

        $this->entityManager->persist($client);
        $this->entityManager->flush();
        return $client;
    }
}
