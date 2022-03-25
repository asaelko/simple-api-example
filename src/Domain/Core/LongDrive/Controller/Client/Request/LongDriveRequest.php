<?php

namespace App\Domain\Core\LongDrive\Controller\Client\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class LongDriveRequest extends AbstractJsonRequest
{
    /**
     * Дата начала ТД (timestamp)
     *
     * @var int|null
     *
     * @Assert\DateTime(format="U")
     */
    public $startAt;

    /**
     * Срок бронирования (в днях)
     *
     * @var int|null
     *
     * @Assert\Type("integer")
     */
    public $period;
}