<?php

namespace AppBundle\Filter;

use CarlBundle\Annotation\RoleAware;
use CarlBundle\Annotation\RoleAwareGroup;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Security\Core\User\UserInterface;

class RoleAwareFilter extends SQLFilter
{
    /** @var Reader */
    protected $Reader;

    /** @var UserInterface */
    protected $User;

    /**
     * Добавляем фильтр, если на сущности доктрины он был определен
     *
     * @param ClassMetaData $targetEntity
     * @param string $targetTableAlias
     * @return string
     *
     * @throws AnnotationException
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (null === $this->Reader) {
            return '';
        }

        $roleAwareGroup = $this->getRoleAwareAnnotation($targetEntity);

        if (!$roleAwareGroup) {
            return '';
        }

        /** @var RoleAware $roleAware */
        foreach ($roleAwareGroup as $roleAware) {
            $fieldName = $roleAware->entityFieldName;

            if (!\in_array($roleAware->role, $this->User->getRoles(), true)) {
                continue;
            }

            // метод, который надо вызывать для получения нужного для фильтрации параметра пользователя
            $userMethodName = 'get'.ucfirst($roleAware->userFieldName);

            try {
                if (\is_callable([$this->User, $userMethodName])) {
                    $filterData = $this->User->$userMethodName();
                } else {
                    throw new AnnotationException(
                        'Invalid UserAware annotation in class '.$targetEntity->getReflectionClass()
                    );
                }
            } catch (\InvalidArgumentException $e) {
                return '';
            }

            if (!$fieldName) {
                return '';
            }

            if (!$filterData) {
                return sprintf('%s.%s IS NULL', $targetTableAlias, $fieldName);
            }

            if (!\is_array($filterData)) {
                return sprintf('%s.%s = %s', $targetTableAlias, $fieldName, $filterData);
            }

            return sprintf('%s.%s IN (%s)', $targetTableAlias, $fieldName, implode(',', $filterData));
        }

        return '';
    }

    /**
     * @param Reader $reader
     */
    public function setAnnotationReader(Reader $reader)
    {
        $this->Reader = $reader;
    }

    /**
     * Устанавливаем текущего пользователя
     *
     * @param UserInterface
     */
    public function setUser($User)
    {
        $this->User = $User;
    }

    /**
     * @param ClassMetaData $targetEntity
     *
     * @return RoleAwareGroup[]
     */
    private function getRoleAwareAnnotation(ClassMetadata $targetEntity): array
    {
        // пытаемся получить группу
        /** @var RoleAwareGroup $roleAwareGroup */
        $roleAwareGroup = $this->Reader->getClassAnnotation(
            $targetEntity->getReflectionClass(),
            RoleAwareGroup::class
        );

        if ($roleAwareGroup) {
            return $roleAwareGroup->rolesAware;
        }

        // пытаемся получить единичную запись
        $roleAware = $this->Reader->getClassAnnotation(
            $targetEntity->getReflectionClass(),
            RoleAware::class
        );

        if ($roleAware) {
            return [$roleAware];
        }

        return [];
    }
}
