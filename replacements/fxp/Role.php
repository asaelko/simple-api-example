<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/** @See: vendor/fxp/security/Model/Role.php */
namespace Fxp\Component\Security\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Fxp\Component\Security\Model\Traits\PermissionsTrait;
use Symfony\Component\Security\Core\Role\Role as BaseRole;

/**
 * This is the domain class for the Role object.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class Role extends BaseRole implements RoleHierarchicalInterface
{
    use PermissionsTrait;

    /**
     * @var int|string|null
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Collection|null
     */
    protected $parents;

    /**
     * @var Collection|null
     */
    protected $children;

    /**
     * Constructor.
     *
     * @param string $name The role name
     */
    public function __construct($name)
    {
        parent::__construct('');

        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addParent(RoleHierarchicalInterface $role)
    {
        $role->addChild($this);
        $this->getParents()->add($role);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeParent(RoleHierarchicalInterface $parent)
    {
        if ($this->getParents()->contains($parent)) {
            $this->getParents()->removeElement($parent);
            $parent->getChildren()->removeElement($this);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParents()
    {
        return $this->parents ?: $this->parents = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentNames()
    {
        $names = [];

        /* @var RoleInterface $parent */
        foreach ($this->getParents() as $parent) {
            $names[] = $parent->getName();
        }

        return $names;
    }

    /**
     * {@inheritdoc}
     */
    public function hasParent($name)
    {
        return \in_array($name, $this->getParentNames());
    }

    /**
     * {@inheritdoc}
     */
    public function addChild(RoleHierarchicalInterface $role)
    {
        $this->getChildren()->add($role);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeChild(RoleHierarchicalInterface $child)
    {
        if ($this->getChildren()->contains($child)) {
            $this->getChildren()->removeElement($child);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return $this->children ?: $this->children = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenNames()
    {
        $names = [];

        /* @var RoleInterface $child */
        foreach ($this->getChildren() as $child) {
            $names[] = $child->getName();
        }

        return $names;
    }

    /**
     * {@inheritdoc}
     */
    public function hasChild($name)
    {
        return \in_array($name, $this->getChildrenNames());
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->getRole();
    }

    /**
     * Clone the role.
     */
    public function __clone()
    {
        $this->id = null;
    }
}
