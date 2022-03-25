<?php


namespace App\Domain\Yandex\Yml\Controller;

use App\Domain\Yandex\Yml\Service\YandexCsvYmlService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Контроллер-генератор YML-фида для Яндекса
 */
class YmlController extends AbstractController
{
    /**
     * YML-фид машин, доступных на тест-драйв
     *
     * @OA\Get(operationId="yandex/feed/yml")
     *
     * @OA\Tag(name="Yandex\Feed")
     *
     * @param YandexCsvYmlService $service
     * @return Response
     */
    public function getYmlAction(YandexCsvYmlService $service): Response
    {
        $xml = $service->generateYmlTestDrive();

        return new Response($xml->asXML());
    }

    /**
     * CSV-фид машин из стоков дилера
     *
     * @OA\Get(operationId="yandex/offers/feed/csv")
     *
     * @OA\Tag(name="Yandex\Feed")
     *
     * @param YandexCsvYmlService $service
     * @return Response
     */
    public function getCsvAction(YandexCsvYmlService $service): Response
    {
        return new Response($service->generateCsvDrive());
    }

    /**
     * YML-фид машин из стоков дилера
     *
     * @OA\Get(operationId="yandex/offers/feed/yml")
     *
     * @OA\Tag(name="Yandex\Feed")
     *
     * @param YandexCsvYmlService $service
     * @return Response
     */
    public function getYmlOfferAction(YandexCsvYmlService $service): Response
    {
        $xml = $service->generateOffersYml();

        return new Response($xml->asXML());
    }
}
