<?php


namespace App\Domain\Core\Leasing;


use App\Domain\Core\Leasing\Response\LeasingResponse;

interface CalculateLeasingInterface
{
    public function calculate(float $cost, int $firstPayPercent, int $term): LeasingResponse;
}