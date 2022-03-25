<?php

namespace App\Domain\Marketing\Interfaces;

use CarlBundle\Entity\Drive;
use DateTime;

interface MarketingClientInterface
{
    /**
     * Обновляет данные по клиенту в маркетинговом деше
     * @param Drive $drive
     * @param DateTime $updatedAt
     */
    public function updateDrive(Drive $drive, DateTime $updatedAt): void;
}