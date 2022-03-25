<?php

namespace App\Domain\Infrastructure\PartnerApi\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;

class PostbackDataRequest extends AbstractJsonRequest
{
    /**
     * Тип записи, по которой передаются данные
     */
    public string $type;

    /**
     * Идентификатор записи, по которой передаются данные
     */
    public int $id;

    /**
     * Данные для обработки
     */
    public array $data;
}
