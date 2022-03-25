<?php

namespace App\Domain\Core\System\Service;

use AppBundle\User\AbstractAuthorizableUser;
use Symfony\Component\Security\Core\Security as CoreSecurity;

/**
 * Расширение класса безопасности Symfony для более удобной работы с системными пользователями
 */
class Security extends CoreSecurity
{
    /**
     * @return AbstractAuthorizableUser|null
     */
    public function getUser(): ?AbstractAuthorizableUser
    {
        /** @var AbstractAuthorizableUser|null $user */
        $user = parent::getUser();

        return $user;
    }
}
