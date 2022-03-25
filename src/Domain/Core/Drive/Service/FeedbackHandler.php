<?php

namespace App\Domain\Core\Drive\Service;

use App\Domain\Core\Drive\Controller\Request\UpdateFeedbackRequest;
use CarlBundle\Entity\Drive;
use CarlBundle\Entity\DriveFeedback;
use CarlBundle\Exception\ValueAlreadyUsedException;

/**
 * Обработчик фидбека пользователя по поездке
 */
class FeedbackHandler
{
    /**
     * Обрабатываем фидбек пользователя по поездке
     *
     * @param Drive                 $drive
     * @param UpdateFeedbackRequest $feedbackRequest
     *
     * @return Drive
     * @throws ValueAlreadyUsedException
     */
    public function applyFeedback(Drive $drive, UpdateFeedbackRequest $feedbackRequest): Drive
    {
        if ($drive->getFeedback()) {
            throw new ValueAlreadyUsedException('Фидбек уже установлен');
        }

        $feedback = new DriveFeedback();
        $feedback->setLiked($feedbackRequest->liked)
            ->setEquipment($feedbackRequest->equipment)
            ->setExterior($feedbackRequest->exterior)
            ->setInterior($feedbackRequest->interior)
            ->setConsultant($feedbackRequest->consultant);

        $tags = array_merge(
            $feedbackRequest->characteristicsTags,
            $feedbackRequest->interiorTags,
            $feedbackRequest->exteriorTags,
            $feedbackRequest->consultantTags
        );

        $tagsValue = 0;
        foreach ($tags as $tag) {
            if (array_key_exists($tag, DriveFeedback::TAGS)) {
                $tagsValue |= DriveFeedback::TAGS[$tag];
            }
        }
        $feedback->setTags($tagsValue);
        $feedback->setDrive($drive);

        $drive->setFeedback($feedback);

        return $drive;
    }
}
