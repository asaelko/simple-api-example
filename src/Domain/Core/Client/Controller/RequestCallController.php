<?php

namespace App\Domain\Core\Client\Controller;

use AppBundle\Service\AppConfig;
use CarlBundle\Entity\Client;
use CarlBundle\Response\Common\BooleanResponse;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class RequestCallController extends AbstractController
{
    private const TARGETS = [
        'gifts' => 'Оформление подарков',
        'offer' => 'Коммерческое предложение',
        'profile' => 'Профиль клиента',
        'long_drive' => 'Лонг-драйв',
        'subscription' => 'Подписка',
        'subscription_query' => 'Запрос на подписку',
    ];

    private GuzzleClient $client;
    private LoggerInterface $logger;
    private AppConfig $appConfig;

    public function __construct(
        GuzzleClient    $client,
        LoggerInterface $albatoLogger,
        AppConfig       $appConfig
    )
    {
        $this->client = $client;
        $this->logger = $albatoLogger;
        $this->appConfig = $appConfig;
    }

    /**
     * Отправка заявки на звонок
     *
     * @OA\Post(
     *     operationId="/client/request-call",
     *     @OA\Parameter(
     *          name="target",
     *          in="query",
     *          description="{gifts,offer,profile}",
     *          required=true,
     *          @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *          name="id",
     *          in="query",
     *          description="Идентификатор сущности, по которой запрашивается звонок",
     *          required=false,
     *          @OA\Schema(type="string")
     *     ),
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет результат запроса",
     *     @OA\JsonContent(
     *          ref=@Model(type=BooleanResponse::class)
     *     )
     * )
     * @OA\Tag(name="Client\Calls")
     * @return BooleanResponse
     */
    public function requestCall(HttpRequest $httpRequest): BooleanResponse
    {
        $target = $httpRequest->get('target');
        $targetId = $httpRequest->get('id');

        if (!$target || !in_array($target, self::TARGETS, true)) {
            return new BooleanResponse(false);
        }

        /** @var Client $client */
        $client = $this->getUser();

        $request = new Request(
            "POST",
            "https://h.albato.ru/wh/38/1lfpnqg/4%252FZt1teARqN3m8ldGn%252BQYCFKC0zSK%252FFKqPuy26TMlFQ%253D/",
            [
                "Connection"   => "keep-alive",
                "Accept"       => "application/json, text/plain, */*",
                "Content-Type" => "application/x-www-form-urlencoded",
                "Origin"       => "https://carl-drive.ru",
                "Referer"      => "https://carl-drive.ru/",
            ],
            http_build_query([
                "id"     => $client->getId(),
                "name"   => (!$this->appConfig->isProd() ? '[TEST]' : '') . $client->getFullName(),
                "phone"  => $client->getPhone(),
                "target" => self::TARGETS[$target] . ($targetId ? " #{$targetId}" : ''),
                "url"    => "carl://" . $target,
            ])
        );

        try {
            $response = $this->client->send($request);
        } catch (GuzzleException $e) {
            $this->logger->info($e);
            return new BooleanResponse(false);
        }
        $this->logger->info('[' . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . '] ' . $response->getBody()->getContents());

        return new BooleanResponse(true);
    }
}