<?php

namespace App\Domain\Core\Client\Service\RequestDTO;

use DateTimeInterface;

abstract class AbstractRequestDTO
{
    abstract public function type(): string;

    abstract public function dateTime(): DateTimeInterface;
}