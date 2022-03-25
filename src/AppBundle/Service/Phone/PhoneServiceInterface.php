<?php

namespace AppBundle\Service\Phone;

use CarlBundle\Exception\CantSendSMSException;

/**
 * Интерфейс работы с номером телефона пользователя
 *
 * Необходим во избежание проблем со сменой смс-провайдера
 */
interface PhoneServiceInterface
{
    /**
     * Получаем информацию по номеру телефона (заодно верифицируем)
     *
     * @param string $phone
     * @return array
     */
    public function getPhoneInfo(string $phone): array;

    /**
     * Отправляем смс на заданный номер
     *
     * @param string $phone
     * @param string $text
     *
     * @throws CantSendSMSException
     */
    public function sendSms(string $phone, string $text): void;
}