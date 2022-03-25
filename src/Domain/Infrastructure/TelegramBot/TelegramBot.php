<?php

namespace App\Domain\Infrastructure\TelegramBot;

use CarlBundle\Entity\Schedule;
use CarlBundle\Helpers\DateFormatterHelper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;

/**
 * Сервис взаимодействия с микросервисом телеграм-бота
 */
class TelegramBot
{
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(
        ParameterBagInterface $parameterBag,
        LoggerInterface $telegramBotLogger
    )
    {
        $this->client = new Client(['base_uri' => $parameterBag->get('driver_bot')['uri']]);
        $this->logger = $telegramBotLogger;
    }

    /**
     * Отправляем запрос тг-боту на поиск водителя для расписания
     * @param Schedule $schedule
     * @return bool
     */
    public function sendNewSurveyNotification(Schedule $schedule): bool
    {
        try {
            $response = $this->client->post(
                '/shift/notify',
                [
                    RequestOptions::JSON =>
                        [
                            'id'          => $schedule->getId(),
                            'description' => $this->getScheduleText($schedule)
                        ]
                ]
            );
            $this->logger->info(
                sprintf('/shift/notify: %s %s', $response->getStatusCode(), $response->getBody()->getContents())
            );
            return true;
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * Отправляем тг-боту уведомление о необходимости прекратить поиск водителя на смену
     *
     * @param Schedule $schedule
     * @return bool
     */
    public function sendStopSurveyNotification(Schedule $schedule): bool
    {
        try {
            $response = $this->client->post(
                "/shift/{$schedule->getId()}/cancel"
            );
            $this->logger->info(
                sprintf('/shift/cancel: %s %s', $response->getStatusCode(), $response->getBody()->getContents())
            );
            return true;
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * @param Schedule $schedule
     * @return string
     */
    private function getScheduleText(Schedule $schedule): string
    {
        $dateTimeStart = clone $schedule->getStart();
        $dateTimeStop = clone $schedule->getStop();
        $dateTimeStart->modify('+3 hour');
        $dateTimeStop->modify('+3 hour');
        try {
            $carModelBrandName = $schedule->getCar()->getCarModelBrandName();
        } catch (Throwable $throwable) {
            $carModelBrandName = '';
        }

        return sprintf(
            '%s, %s: %s - %s, на %s',
            $dateTimeStart->format('d.m'),
            DateFormatterHelper::getNameWeekDay($dateTimeStart->format('N')),
            $dateTimeStart->format('H:i'),
            $dateTimeStop->format('H:i'),
            $carModelBrandName
        );
    }
}
