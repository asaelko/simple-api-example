<?php

namespace App\Domain\Notifications\Push\Builder;

use App\Domain\Notifications\Push\AbstractBuildablePushMessage;
use App\Domain\Notifications\Push\PartnersPushMessage;
use App\Domain\Notifications\Push\PushMessageInterface;
use App\Domain\Notifications\Push\Sender\GoRush\PushMessage;
use AppBundle\Service\AppConfig;
use CarlBundle\Entity\Interfaces\UserPushInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Билдер пуш-уведомлений для GoRush
 */
class PushNotificationBuilder
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Собираем данные для пуш-уведомления
     *
     * @param PushMessageInterface $pushData
     * @return PushMessage|null
     */
    public function build(PushMessageInterface $pushData): ?PushMessage
    {
        if (($pushData instanceof PartnersPushMessage) && !$pushData->checkOfferNotBook($this->entityManager)) {
            $this->logger->info('Оффер был забронирован');
            return null;
        }

        // пусть пуш сам соберет себя, если он умеет
        if ($pushData instanceof AbstractBuildablePushMessage) {
            try {
                $pushData->build($this->entityManager);
            } catch (Exception $ex) {
                $this->logger->error($ex);
                return null;
            }
        }

        // а теперь определяем получателя пуша
        $receivers = [];
        foreach ($pushData->getReceivers() as $className => $ids) {
            foreach ($this->resolveReceivers($className, $ids) as $receiver) {
                $appTag = method_exists($receiver, 'getAppTag') ? $receiver->getAppTag() : AppConfig::WL_MAIN;

                if (!isset($receivers[$appTag])) {
                    $receivers[$appTag] = [];
                }

                if (!isset($receivers[$appTag][$receiver->getMobileOs()])) {
                    $receivers[$appTag][$receiver->getMobileOs()] = [];
                }
                $receivers[$appTag][$receiver->getMobileOs()][] = $receiver->getPushToken();
            }
        }

        return new PushMessage(
            $pushData->getTitle(),
            $pushData->getText(),
            $receivers,
            $pushData->getData(),
            null,
            $pushData->getImage(),
            $pushData->getContext()
        );
    }

    /**
     * @param string $className
     * @param array $ids
     * @return array|UserPushInterface[]
     */
    private function resolveReceivers(string $className, array $ids): array
    {
        return $this->entityManager->getRepository($className)->findBy([
            'id' => $ids
        ]);
    }
}
