<?php

namespace AppBundle\Request;

use Bilyiv\RequestDataBundle\Formats;
use Bilyiv\RequestDataBundle\FormatSupportableInterface;
use Bilyiv\RequestDataBundle\RequestDataInterface;

class AbstractJsonRequest implements RequestDataInterface, FormatSupportableInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSupportedFormats(): array
    {
        return [Formats::JSON, Formats::FORM];
    }
}
