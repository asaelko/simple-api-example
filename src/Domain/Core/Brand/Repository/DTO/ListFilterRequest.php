<?php


namespace App\Domain\Core\Brand\Repository\DTO;

/**
 * Запрос фильтрации брендов
 */
class ListFilterRequest
{
    public array $cities = [];

    public array $brands = [];
}
