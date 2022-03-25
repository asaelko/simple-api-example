<?php


namespace App\Domain\Notifications\Messages\Driver\TestDrive\TgMessage;


use CarlBundle\Entity\Car;
use CarlBundle\Entity\Driver;

class CarChangeReminderTelegramMessage extends AbstractDriverTelegramMessage
{
    private const TEXT = 'Через час начинается смена на %s. Не забудьте сменить машину';

    public function __construct(Driver $driver, Car $car)
    {
        $this->text = sprintf(
            $this->addNameToText($driver->getFirstName(), self::TEXT),
            $car->getModel()->getNameWithBrand()
        );
        $this->driver = $driver->getId();
    }
}