<?php

namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;

use CarlBundle\Entity\Driver;

/**
 * Системный пуш водительскому приложению о том, что акт подписан
 */
class TestDriveAgreementSignedTelegramMessage extends AbstractDriverTelegramMessage
{
    private const TEXT = 'Пользователь подписал акт, можно ехать';

    public function __construct(Driver $driver)
    {
        $this->text = self::TEXT;
        $this->driver = $driver->getId();
    }
}
