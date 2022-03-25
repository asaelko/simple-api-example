<?php


namespace App\Domain\Core\ExperienceCenters\Request;


use AppBundle\Request\AbstractJsonRequest;

class AdminGetCenterRequest extends AbstractJsonRequest
{
    /**
     * @var int|null
     */
    public ?int $brandId = null;
}