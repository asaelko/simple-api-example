<?php

namespace App\Domain\Core\Client\Controller\Request;

/**
 * Интерфейс запроса на регистрацию клиента
 */
interface ClientPhoneRegistrationRequestInterface
{
    /**
     * @return string
     */
    public function getFirstName(): string;

    /**
     * @return string|null
     */
    public function getPhoneToken(): ?string;

    /**
     * @return string|null
     */
    public function getPhone(): ?string;
}
