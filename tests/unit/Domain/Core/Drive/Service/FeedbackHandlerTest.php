<?php

namespace UnitTest\Domain\Core\Drive\Service;

use App\Domain\Core\Drive\Controller\Request\UpdateFeedbackRequest;
use App\Domain\Core\Drive\Service\FeedbackHandler;
use CarlBundle\Entity\Drive;
use CarlBundle\Entity\DriveFeedback;
use CarlBundle\Exception\ValueAlreadyUsedException;
use Codeception\Test\Unit;
use Exception;

class FeedbackHandlerTest extends Unit
{
    private FeedbackHandler $feedbackHandler;

    protected function _inject(FeedbackHandler $feedbackHandler)
    {
        $this->feedbackHandler = $feedbackHandler;
    }

    /**
     * Тестируем обработку запроса установки фидбека
     *
     * @dataProvider feedbackParamsData
     *
     * @throws Exception
     */
    public function testApplyFeedback(array $feedbackParams, int $tagsValue): void
    {
        $drive = $this->make(Drive::class);
        $feedbackRequest = $this->make(UpdateFeedbackRequest::class, $feedbackParams);

        $this->feedbackHandler->applyFeedback($drive, $feedbackRequest);

        self::assertNotNull($drive->getFeedback(), 'Фидбек не установился');
        self::assertNotNull($drive->getFeedback()->getDrive(), 'Фидбек не привязан к поездке');
        self::assertEquals($drive->getFeedback()->getTags(), $tagsValue, 'Теги установлены неправильно');

        foreach($feedbackParams as $paramName => $expectedValue) {
            $methodName = 'get'.ucfirst($paramName);
            if (method_exists($drive->getFeedback(), $methodName)) {
                self::assertEquals($expectedValue, $drive->getFeedback()->$methodName(), "$methodName отдал неверное значение");
            }
        }
    }

    /**
     * Тестируем установку фидбека на ТД с уже установленным фидбеком
     *
     * @covers \App\Domain\Core\Drive\Service\FeedbackHandler::applyFeedback
     *
     * @throws Exception
     */
    public function testApplyFeedbackTwice(): void
    {
        $drive = $this->make(Drive::class, ['getFeedback' => new DriveFeedback()]);
        $feedbackRequest = $this->make(UpdateFeedbackRequest::class, []);

        $this->expectException(ValueAlreadyUsedException::class);
        $this->feedbackHandler->applyFeedback($drive, $feedbackRequest);
    }

    /**
     * @return array[]
     */
    public function feedbackParamsData(): array
    {
        return [
            // кейс 1: нормальные данные, нормальные теги
            [
                [
                    'liked'               => true,
                    'equipment'           => 0.5,
                    'exterior'            => 0.5,
                    'interior'            => 0.5,
                    'consultant'          => 0.5,
                    'characteristicsTags' => ['acceleration'],
                ],
                DriveFeedback::ACCELERATION_TAG,
            ],

            // кейс 2: только теги
            [
                [
                    'characteristicsTags' => ['acceleration'],
                    'exteriorTags' => ['baggage'],
                    'interiorTags' => ['multimedia', 'audio'],
                    'consultantTags' => ['talk'],
                ],
                DriveFeedback::ACCELERATION_TAG
                | DriveFeedback::EXTERIOR_BAGGAGE_TAG
                | DriveFeedback::INTERIOR_MULTIMEDIA_TAG | DriveFeedback::INTERIOR_AUDIO_TAG
                | DriveFeedback::CONSULTANT_TALK_TAG,
            ],

            // кейс 3: неизвестные теги
            [
                [
                    'characteristicsTags' => ['acceleration', 'kaif', 'lol'],
                ],
                DriveFeedback::ACCELERATION_TAG
            ],
        ];
    }
}
