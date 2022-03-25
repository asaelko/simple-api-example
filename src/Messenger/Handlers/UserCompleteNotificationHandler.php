<?php

namespace App\Messenger\Handlers;

use App\Messenger\Messages\UserCompleteNotificationMessage;
use CarlBundle\Repository\ClientRepository;
use CarlBundle\Service\SlackNotificatorService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserCompleteNotificationHandler implements MessageHandlerInterface
{
    private ClientRepository $clientRepository;

    private SlackNotificatorService $slackNotification;

    public function __construct(
        ClientRepository $clientRepository,
        SlackNotificatorService $slackNotification
    )
    {
        $this->clientRepository = $clientRepository;
        $this->slackNotification = $slackNotification;
    }

    public function __invoke(UserCompleteNotificationMessage $message)
    {
        $client = $this->clientRepository->find($message->getId());
        if (!$client || !$client->isCompleted()) {
            return;
        }
        $this->slackNotification->sendClientCompleteNotification($client);
    }
}