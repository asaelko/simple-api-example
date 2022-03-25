<?php

namespace App\Domain\Core\Model\Controller\Response;

use CarlBundle\Entity\CarTarget\AbstractCarTarget;
use OpenApi\Annotations as OA;

/**
 * Объект таргета для модели
 */
class TargetResponse
{
    /**
     * Блокирует ли таргет возможность прохождения тест-драйва на этой модели автомобиля
     */
    public bool $blocking;

    /**
     * @OA\Property(type="array",
     *      @OA\Items(
     *          @OA\Property(property="passed", type="boolean", description="Пройдена ли проверка"),
     *          @OA\Property(property="description", type="string", description="Описание проверки", example="Водительский стаж более 3 лет"),
     *          @OA\Property(property="link", type="string", description="Диплинк на раздел, где нужно внести изменения", example="carl://profile")
     *      )
     * )
     */
    public array $blockingChecks;

    /**
     * Описание действия, которое необходимо совершить пользователю для прохождения таргетинга
     */
    public string $actionText;

    /**
     * Заголовок кнопки действия
     */
    public ?string $actionTitle = null;

    /**
     * Диплинк на экран совершения действия для прохождения таргетинга
     */
    public ?string $actionLink = null;

    /**
     * Общее описание таргета
     */
    public ?string $description = null;

    public function __construct(AbstractCarTarget $target)
    {
        $this->actionTitle = $target->getActionTitle();
        $this->actionText = $target->getActionText();
        $this->actionLink = $target->getActionDeepLink();

        $this->description = $target->getDescription();

        $this->blocking = !$target->isPayable();
        $this->blockingChecks = $target->validate();
    }
}
