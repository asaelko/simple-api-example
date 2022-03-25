<?php

namespace App\Monolog\Formatters;

use DateTime;
use Monolog\Formatter\ElasticaFormatter as BaseElasticaFormatter;

class ElasticaFormatter extends BaseElasticaFormatter
{
    public function __construct(string $index, string $type)
    {
        $index = (new DateTime)->format($index);
        parent::__construct($index, $type);
    }
}