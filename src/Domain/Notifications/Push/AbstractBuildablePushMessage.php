<?php

namespace App\Domain\Notifications\Push;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Класс пуш-уведомления, которое способно обновлять свой текст по актуальным данным
 */
class AbstractBuildablePushMessage extends AbstractPushMessage
{
    /**
     * Если пушу необходимо себя собрать перед отправкой - делаем это тут
     * @param EntityManagerInterface $entityManager
     */
    public function build(EntityManagerInterface $entityManager): void
    {

    }
}
