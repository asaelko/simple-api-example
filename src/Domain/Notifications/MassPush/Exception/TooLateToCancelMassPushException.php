<?php

namespace App\Domain\Notifications\MassPush\Exception;

use CarlBundle\Exception\RestException;

/**
 * Слишком поздно отменять масспуш
 */
class TooLateToCancelMassPushException extends RestException
{
    // отвечаем кодом "слишком поздно"
    public const HTTP_CODE = 477;
}