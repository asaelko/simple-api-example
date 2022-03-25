<?php

namespace App\Domain\Notifications\MassPush\Exception;

use CarlBundle\Exception\RestException;

/**
 * Слишком много масспушей за период времени
 */
class NonUniqueMassPushException extends RestException
{
    // отвечаем кодом "лимит исчерпан"
    public const HTTP_CODE = 492;
}