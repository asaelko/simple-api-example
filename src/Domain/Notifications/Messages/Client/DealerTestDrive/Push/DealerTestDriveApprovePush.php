<?php

namespace App\Domain\Notifications\Messages\Client\DealerTestDrive\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Dealer;

final class DealerTestDriveApprovePush extends AbstractPushMessage
{
    private const TITLE = 'client.dealer_test_drive.approved.title';
    private const TEXT = 'client.dealer_test_drive.approved.text';

    public function __construct(Client $client, Dealer $dealer, \DateTimeInterface $dateTime)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $client->getFirstName() ? $client->getFirstName() . ', ' : '',
            'dealerName' => $dealer->getName(),
            'startDate' => sprintf('%s \в %s', $dateTime->format('d-m-Y'), $dateTime->format('H:i')),
        ];

        $this->receivers = [Client::class => [$client->getId()]];
    }
}