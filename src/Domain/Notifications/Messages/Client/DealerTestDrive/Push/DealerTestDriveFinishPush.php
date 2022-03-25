<?php


namespace App\Domain\Notifications\Messages\Client\DealerTestDrive\Push;

use App\Domain\Notifications\Push\AbstractPushMessage;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Dealer;
use CarlBundle\Entity\Model\Model;

final class DealerTestDriveFinishPush extends AbstractPushMessage
{
    private const TITLE = 'client.dealer_test_drive.finished.title';
    private const TEXT = 'client.dealer_test_drive.finished.title';

    public function __construct(Client $client, Dealer $dealer, Model $model)
    {
        $this->title = self::TITLE;
        $this->text = self::TEXT;
        $this->context = [
            'clientName' => $client->getFirstName() ? $client->getFirstName() . ', ' : '',
            'dealerName' => $dealer->getName(),
            'modelName' => $model->getNameWithBrand()
        ];

        $this->receivers = [Client::class => [$client->getId()]];
    }
}