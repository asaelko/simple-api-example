<?php

namespace AppBundle\Filter;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Включение фильтра на все запросы в доктрину, если пользователь залогинен
 *
 * @author Gleb Bogdevich
 */
class RoleAwareConfigurator
{
    /** @var EntityManagerInterface */
    protected $Em;

    /** @var TokenStorageInterface */
    protected $TokenStorage;

    /** @var Reader */
    protected $Reader;

    /**
     * UserAwareConfigurator constructor
     *
     * @param EntityManagerInterface $Em
     * @param TokenStorageInterface $TokenStorage
     * @param Reader $Reader
     */
    public function __construct(EntityManagerInterface $Em, TokenStorageInterface $TokenStorage, Reader $Reader)
    {
        $this->Em = $Em;
        $this->TokenStorage = $TokenStorage;
        $this->Reader = $Reader;
    }

    /**
     * Внедряемся в цепочку событий
     */
    public function onKernelRequest(): void
    {
        if ($User = $this->getUser()) {
            /** @var RoleAwareFilter $filter */
            $filter = $this->Em->getFilters()->enable('role_aware_filter');
            $filter->setAnnotationReader($this->Reader);
            $filter->setUser($User);
        }
    }

    /**
     * Получаем текущего залогиненого пользователя
     *
     * @return mixed|null
     */
    private function getUser()
    {
        $Token = $this->TokenStorage->getToken();

        if (!$Token) {
            return null;
        }

        $User = $Token->getUser();

        if (!($User instanceof UserInterface)) {
            return null;
        }

        return $User;
    }
}
