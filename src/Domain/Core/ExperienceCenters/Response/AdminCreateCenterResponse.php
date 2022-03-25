<?php


namespace App\Domain\Core\ExperienceCenters\Response;


class AdminCreateCenterResponse
{
    /**
     * @var bool
     */
    public bool $status;

    public function __construct(bool $status)
    {
        $this->status = $status;
    }
}