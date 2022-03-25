<?php


namespace App\Domain\Notifications\Push;

use CarlBundle\Entity\Client;

/**
 * Простой пуш
 */
class SimplePushMessage extends AbstractPushMessage
{
    public function __construct(Client $client, ?string $title, string $text, array $data = []) {
        $this->title = $title;
        $this->text = $text;
        $this->receivers = [Client::class => [$client->getId()]];
        $this->data = $data;
    }
}
