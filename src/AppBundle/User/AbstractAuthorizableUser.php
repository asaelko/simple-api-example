<?php

namespace AppBundle\User;

use CarlBundle\Entity\Client;
use CarlBundle\Entity\Driver;
use CarlBundle\Entity\Traits\LogInLoggableTrait;
use CarlBundle\Entity\User;
use function in_array;

/**
 * Абстрактный класс авторизуемых пользователей с реализацией функций-хэлперов для определения роли
 */
abstract class AbstractAuthorizableUser implements UserInterface
{
    use LogInLoggableTrait;

    /**
     * Проверяем, является ли пользователь внешним клиентом
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return ($this instanceof Client);
    }

    /**
     * Проверяем, является ли пользователь внутренним пользователем
     *
     * @return bool
     */
    public function isCoreUser(): bool
    {
        return ($this instanceof User);
    }

    /**
     * Проверяем, является ли пользователь администратором
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return ($this instanceof User) && in_array(self::ADMIN_ROLE, $this->getRoles(), false);
    }

    /**
     * Проверяем, является ли пользователь менеджером водителей
     *
     * @return bool
     */
    public function isDriverManager(): bool
    {
        return ($this instanceof User) && in_array(self::DRIVER_MANAGER_ROLE, $this->getRoles(), false);
    }

    /**
     * Проверяем, является ли пользователь сотрудником колл-центра
     *
     * @return bool
     */
    public function isCallCenter(): bool
    {
        return ($this instanceof User) && in_array(self::CALL_CENTER_ROLE, $this->getRoles(), false);
    }

    /**
     * Проверяем, является ли пользователь менеджером дилеров
     *
     * @return bool
     */
    public function isDealerManager(): bool
    {
        return ($this instanceof User) && in_array(self::DEALER_MANAGER_ROLE, $this->getRoles(), false);
    }

    /**
     * Проверяем, является ли пользователь менеджером брендов
     *
     * @return bool
     */
    public function isBrandManager(): bool
    {
        return ($this instanceof User) && in_array(self::BRAND_MANAGER_ROLE, $this->getRoles(), false);
    }

    /**
     * Проверяем, является ли пользователь управляющим партнера лонг-драйвов
     *
     * @return bool
     */
    public function isLongDrivePartner(): bool
    {
        return ($this instanceof User) && in_array(self::LONGDRIVE_PARTNER_ROLE, $this->getRoles(), false);
    }

    /**
     * Проверяем, является ли пользователь водителем
     *
     * @return bool
     */
    public function isDriver(): bool
    {
        return ($this instanceof Driver);
    }
}
