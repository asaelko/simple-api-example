<?php

namespace App\Domain\Core\Purchase\Controller\Client;

use App\Domain\Core\Partners\Repository\PartnersRepository;
use App\Domain\Core\Purchase\Controller\Client\Request\UpdatePurchaseRequest;
use App\Domain\Core\Purchase\Controller\Client\Response\PurchaseResponse;
use App\Domain\Core\Purchase\Service\PurchaseService;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use CarlBundle\Entity\Partner;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Exception\RestException;
use CarlBundle\Exception\ValueAlreadyUsedException;
use CarlBundle\Factory\Purchase\PurchaseFactory;
use CarlBundle\Request\Purchase\CreatePurchaseRequest;
use DealerBundle\Entity\DriveOffer;
use DealerBundle\Repository\DriveOfferRepository;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PurchaseClientController extends AbstractController
{
    private PurchaseService $purchaseService;
    private DriveOfferRepository $offerRepository;
    private PartnersRepository $partnersRepository;
    private PurchaseFactory $purchaseFactory;

    public function __construct(
        PurchaseService $purchaseService,
        PurchaseFactory $purchaseFactory,
        DriveOfferRepository $offerRepository,
        PartnersRepository $partnersRepository
    )
    {
        $this->purchaseService = $purchaseService;
        $this->offerRepository = $offerRepository;
        $this->partnersRepository = $partnersRepository;
        $this->purchaseFactory = $purchaseFactory;
    }

    /**
     * Получить состояние текущей заявки на покупку
     *
     * @OA\Get(operationId="purchase/current")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт статус заявки",
     *     @OA\JsonContent(
     *          @OA\Property(
     *                  property="id",
     *                  description="Идентификатор поданной заявки, если есть",
     *                  type="string",
     *                  example="3894bf8e271811eab3720242ac110003",
     *                  nullable=true
     *          ),
     *          @OA\Property(
     *              property="state",
     *              type="object",
     *              description="Статус заявки",
     *                  @OA\Property(
     *                      property="decision",
     *                      description="Код статуса заявки",
     *                      type="integer",
     *                      example="0",
     *                      nullable=true
     *                  ),
     *                  @OA\Property(
     *                      property="header",
     *                      description="Текст для отображения на экране",
     *                      type="string",
     *                      example="Купили авто с помощью CARL?"
     *                  ),
     *                  @OA\Property(
     *                      property="text",
     *                      description="Текст для отображения на экране",
     *                      type="string",
     *                      example="Заберите ваши подарки тут!"
     *                  )
     *      )
     *    )
     * )
     *
     * @OA\Tag(name="Client\Purchase")
     *
     * @return array|PurchaseResponse
     */
    public function getCurrentPurchase()
    {
        $purchase = $this->purchaseService->getActivePurchase();

        $result = [
            'state' => [
                'header' => 'Купили авто с помощью CARL?',
                'text' => 'Заберите ваши подарки тут!'
            ]
        ];

        if (!$purchase) {
            return $result;
        }

        return new PurchaseResponse($purchase);
    }

    /**
     * Параметры опроса перед отправкой заявки
     *
     * @OA\Get(operationId="purchase/request/params")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт параметры опроса",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="params",
     *              type="array",
     *              description="Параметры опроса",
     *              @OA\Items(
     *                  @OA\Property(
     *                      property="text",
     *                      description="Текст варианта опроса",
     *                      type="string",
     *                      example="После тест-драйва удалось определиться с моделью"
     *                  ),
     *                  @OA\Property(
     *                      property="options",
     *                      description="Массив параметров ответа",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="id",
     *                              description="Идентификатор модели",
     *                              type="integer",
     *                              example="559"
     *                          ),
     *                          @OA\Property(
     *                              property="name",
     *                              description="Название модели",
     *                              type="string",
     *                              example="Tesla Model 3"
     *                          ),
     *                          @OA\Property(
     *                              property="photo",
     *                              description="Ссылка на картинку",
     *                              type="string",
     *                              example="https://carl-drive.ru/blank.png"
     *                          )
     *                      )
     *                   ),
     *                   @OA\Property(
     *                      property="no_options",
     *                      description="Объект со статусом отсутствия опций. Будет приходить только в том случае, если опций нет",
     *                      type="object",
     *                      nullable=true,
     *                          @OA\Property(
     *                              property="icon",
     *                              description="Эмоджи для отображения",
     *                              type="string",
     *                              example="🤔"
     *                          ),
     *                          @OA\Property(
     *                              property="text",
     *                              description="Текст для отображения",
     *                              type="string",
     *                              example="Закажите тест-драйв"
     *                          )
     *                   )
     *          )
     *      )
     *    )
     * )
     *
     * @OA\Tag(name="Client\Purchase")
     *
     * @return array
     */
    public function getRequestParams(): array
    {
        /** @var Client $client */
        $client = $this->getUser();

        $activeDrives = $client->getDrivesByStatus(Drive::$finishedStates)->toArray();
        $drivesModels = [];
        array_walk($activeDrives, static function(Drive $drive) use (&$drivesModels) {
            $drivesModels[$drive->getCar()->getModel()->getNameWithBrand()] = [
                'id' => $drive->getCar()->getModel()->getId(),
                'name' => $drive->getCar()->getModel()->getNameWithBrand(),
                'photo' => $drive->getCar()->getModel()->getAppPhoto()
            ];
        });
        $drivesModels = array_values($drivesModels);

        $offers = $this->offerRepository->findForClientProfile($client);
        $offersModels = [];
        array_walk($offers, static function(DriveOffer $offer) use (&$offersModels) {
            $offersModels[$offer->getDealerCar()->getEquipment()->getModel()->getNameWithBrand()] = [
                'id' => $offer->getDealerCar()->getEquipment()->getModel()->getId(),
                'name' => $offer->getDealerCar()->getEquipment()->getModel()->getNameWithBrand(),
                'photo' => $offer->getDealerCar()->getEquipment()->getModel()->getAppPhoto(),
            ];
        });
        $offersModels = array_values($offersModels);

        $params = [
            'params' => [
                [
                    'text' => 'После тест-драйва удалось определиться с моделью',
                    'options' => $drivesModels,
                    'no_options' => $drivesModels ? null : [
                        'icon' => '🤔',
                        'text' => 'Хм, кажется у вас еще не было тест-драйвов. Забронируйте свою первую поездку на главной странице!',
                    ],
                ],
                [
                    'text' => 'Дилер прислал выгодное коммерческое предложение',
                    'options' => $offersModels,
                    'no_options' => $offersModels ? null : [
                        'icon' => '😬',
                        'text' => 'Вы пока не запросили ни одного предложения. Выберите автомобиль, а мы подберем лучшую цену!',
                    ],
                ],
            ]
        ];

        return $params;
    }

    /**
     * Список доступных подарков при подтверждении заявки
     *
     * @OA\Get(operationId="purchase/gifts")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список партнеров",
     *     @OA\JsonContent(
     *          type="array",
     *          @OA\Items(
     *                  @OA\Property(
     *                      property="title",
     *                      description="Название подарка",
     *                      type="string",
     *                      example="Топливная карта"
     *                  ),
     *                  @OA\Property(
     *                      property="subtitle",
     *                      description="Подпись подарка",
     *                      type="string",
     *                      example="Осталось загрузить чек"
     *                  ),
     *                  @OA\Property(
     *                      property="description",
     *                      description="Подробное описание подарка",
     *                      type="string",
     *                      example="<p>Топливная карта на 60 литров при покупке...</p>"
     *                  ),
     *                  @OA\Property(
     *                      property="photo",
     *                      description="Объект фотографии для отображения",
     *                      type="object"
     *                  ),
     *                  @OA\Property(
     *                      property="splashPhoto",
     *                      description="Объект фотографии для детального экрана",
     *                      type="object"
     *                  )
     *      )
     *    )
     * )
     *
     * @OA\Tag(name="Client\Purchase")
     *
     * @return array
     */
    public function getGifts(): array
    {
        $result = array_map(
            static function(Partner $partner) {
                return [
                    'title' => $partner->getSubtitle(),
                    'photo' => $partner->getPhoto(),
                    'description' => $partner->getDescription(),
                    'splashPhoto' => $partner->getSplashPhoto(),
                    'storyPhoto' => $partner->getStoryPhoto(),
                    'subtitle' => 'Осталось загрузить чек'
                ];
            },
            $this->partnersRepository->getActive()
        );

        return $result;
    }

    /**
     * Отправка заявки на покупку
     *
     * @OA\Post(
     *     operationId="purchase/create",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  ref=@Model(type=CreatePurchaseRequest::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет созданную заявку",
     *     @OA\JsonContent(
     *          ref=@Model(type=PurchaseResponse::class)
     *    )
     * )
     *
     * @OA\Tag(name="Client\Purchase")
     *
     * @param CreatePurchaseRequest $request
     *
     * @return PurchaseResponse
     * @throws InvalidValueException
     * @throws RestException
     * @throws ValueAlreadyUsedException
     */
    public function createRequest(CreatePurchaseRequest $request): PurchaseResponse
    {
        $Purchase = $this->purchaseFactory->create($request);

        $this->purchaseService->processPurchaseRequest($Purchase);

        return new PurchaseResponse($Purchase);
    }

    /**
     * Обновление заявки на покупку
     *
     * @OA\Post(
     *     operationId="purchase/update",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  ref=@Model(type=UpdatePurchaseRequest::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет обновленную заявку",
     *     @OA\JsonContent(
     *          ref=@Model(type=PurchaseResponse::class)
     *    )
     * )
     *
     * @OA\Tag(name="Client\Purchase")
     *
     * @param UpdatePurchaseRequest $request
     * @param string                $purchaseId
     *
     * @return PurchaseResponse
     *
     * @throws RestException
     * @throws ValueAlreadyUsedException
     */
    public function updateRequest(UpdatePurchaseRequest $request, string $purchaseId): PurchaseResponse
    {
        $purchase = $this->purchaseService->get($purchaseId);
        $receiptPhoto = $this->purchaseFactory->resolveReceiptPhoto($request->receiptPhotoId);
        if (!$receiptPhoto) {
            throw new InvalidValueException('Фотография не найдена');
        }

        $purchase->setReceiptPhoto($receiptPhoto);

        $this->purchaseService->processPurchaseRequest($purchase);

        return new PurchaseResponse($purchase);
    }
}
