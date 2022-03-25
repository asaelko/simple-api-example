<?php

namespace AppBundle\User;

use Symfony\Component\Security\Core\User\UserInterface as CoreUserInterface;

/**
 * Общий интерфейс для всех пользователей с возможностью входа
 */
interface UserInterface extends CoreUserInterface
{
    public const ADMIN_ROLE = 'ROLE_ADMIN_USER';
    public const CLIENT_ROLE = 'ROLE_API_USER';
    public const DRIVER_ROLE = 'ROLE_DRIVER';
    public const DRIVER_MANAGER_ROLE = 'ROLE_DRIVER_MANAGER';
    public const CALL_CENTER_ROLE = 'ROLE_CALL_CENTER';

    public const DEALER_MANAGER_ROLE = 'ROLE_DEALER_MANAGER';
    public const BRAND_MANAGER_ROLE = 'ROLE_BRAND_MANAGER';

    public const LONGDRIVE_PARTNER_ROLE = 'ROLE_LONGDRIVE_PARTNER';
}