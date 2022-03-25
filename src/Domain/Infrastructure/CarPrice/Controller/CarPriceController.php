<?php


namespace App\Domain\Infrastructure\CarPrice\Controller;


use App\Domain\Infrastructure\CarPrice\Request\CreateCarPriceRequest;
use App\Domain\Infrastructure\CarPrice\Response\OfficeResponse;
use App\Domain\Infrastructure\CarPrice\Service\CarPriceService;
use App\Entity\CarPriceCar;
use App\Entity\CarPriceOffice;
use App\Entity\CarPriceOrder;
use CarlBundle\Entity\Client;
use Exception;
use Nelmio\ApiDocBundle\Annotation\Model;
use CarlBundle\Entity\ClientCar;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function Clue\StreamFilter\fun;

class CarPriceController extends AbstractController
{
    /**
     * Отправка запроса в CarPrice
     *
     * Производит отправку и создание ордера в кц для кар прайс
     *
     * @OA\Post(
     *     operationId="/client/car-price/order",
     *     @OA\RequestBody(
     *          @Model(type=CreateCarPriceRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет идентификатор созданной встречи",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="code",
     *              type="string",
     *              example="12qewqljdjqknwn2"
     *          )
     *     )
     * )
     * @OA\Tag(name="Client\CarPrice")
     * @param CreateCarPriceRequest $request
     * @param CarPriceService $service
     * @return JsonResponse
     * @throws Exception
     */
    public function createCarPriceOrder(
        CreateCarPriceRequest $request,
        CarPriceService $service
    ): JsonResponse
    {
        $client = $this->getUser();
        $car = $this->getDoctrine()->getRepository(ClientCar::class)->find($request->clientCar);
        if (!($car instanceof ClientCar) || $client !== $car->getClient()) {
            throw new NotFoundHttpException('Клиентская машина не найдена');
        }
        $carPriceCar = $this->getDoctrine()->getRepository(CarPriceCar::class)->findOneBy(['clientCar' => $car]);
        if (!($carPriceCar instanceof CarPriceCar)) {
            throw new NotFoundHttpException('Не найден просчет по данной машине');
        }
        $office = $this->getDoctrine()->getRepository(CarPriceOffice::class)->find($request->locationId);
        if (!($office instanceof CarPriceOffice)) {
            throw new NotFoundHttpException('Оффис не найден');
        }
        $date = (new \DateTime)->setTimeStamp($request->time);
        $result = $service->createOrder($carPriceCar, $date, $office);

        $order = new CarPriceOrder();
        $order->setCarPriceCar($carPriceCar);
        $order->setCarPriceId($result['code']);

        $this->getDoctrine()->getManager()->persist($order);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['code' => $order->getCarPriceId()]);
    }

    /**
     * Получить список офисоф для мск
     *
     * @OA\Get(
     *     operationId="/client/car-price/office"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт список офисов",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              type="array",
     *              @OA\Items(ref=@Model(type=OfficeResponse::class))
     *          )
     *     )
     * )
     * @OA\Tag(name="Client\CarPrice")
     * @return JsonResponse
     */
    public function getOfficeList(): JsonResponse
    {
        $offices = $this->getDoctrine()->getRepository(CarPriceOffice::class)->findAll();
        $result = array_map(
            function (CarPriceOffice $office) {
                return new OfficeResponse($office);
            },
            $offices
        );
        return new JsonResponse($result);
    }
}