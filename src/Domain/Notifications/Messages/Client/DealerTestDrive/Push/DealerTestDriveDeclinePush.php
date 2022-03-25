<?php

namespace App\Domain\Notifications\Messages\Client\DealerTestDrive\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Dealer;

final class DealerTestDriveDeclinePush extends AbstractPushMessage
{
    private const TITLE = 'client.dealer_test_drive.declined.title';
    private const TEXT = 'client.dealer_test_drive.declined.title';

    public function __construct(Client $client, Dealer $dealer)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $client->getFirstName() ? $client->getFirstName() . ', ' : '',
            'dealerName' => $dealer->getName()
        ];

        $this->receivers = [Client::class => [$client->getId()]];
    }
}