<?php

namespace App\Domain\System\EventListener;

use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;

/**
 * Переопределяем класс Gedmo для логирования дополнительных опций изменений
 */
class LoggableListener extends \Gedmo\Loggable\LoggableListener
{
    /**
     * Set username for identification
     *
     * @param mixed $token
     *
     * @throws InvalidArgumentException Invalid username
     */
    public function setUsername($token)
    {
        if (is_string($token)) {
            $this->username = $token;
        } elseif (is_object($token) && method_exists($token, 'getUser')) {
            $user = $token->getUser();
            $this->username = sprintf('%s#%d', get_class($user), $user->getId());
        } else {
            throw new InvalidArgumentException('Username must be a string, or object should have method: getUsername');
        }
    }

    /**
     * @param string          $action
     * @param object          $object
     * @param LoggableAdapter $ea
     *
     * @return LogEntry|AbstractLogEntry|null
     */
    protected function createLogEntry($action, $object, LoggableAdapter $ea)
    {
        if ($this->username === null) {
            $this->username = implode(' ', $_SERVER['argv'] ?? ['anon']);
        }
        return parent::createLogEntry($action, $object, $ea);
    }
}
