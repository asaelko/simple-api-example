<?php

namespace App\Domain\Yandex\TurboApp\Exception;

use CarlBundle\Exception\RestException;

/**
 * Ошибка получения данных от Яндекса
 */
class NoAuthDataException extends RestException
{
    public const HTTP_CODE = 523;
}
