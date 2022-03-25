<?php


namespace App\Domain\Core\Leasing\Controller;


use App\Domain\Core\Leasing\Request\LeasingRequest;
use App\Domain\Core\Leasing\Request\ProvideLeasingRequest;
use App\Domain\Core\Leasing\Request\SendEmailLeasingRequest;
use App\Domain\Core\Leasing\Response\LeasingResponse;
use App\Domain\Core\Leasing\Service\LeasingService;
use CarlBundle\Entity\Leasing\LeasingProvider;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Exception\RestException;
use CarlBundle\Service\Leasing\LeasingRequestService;
use DealerBundle\Entity\Car;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use CarlBundle\Entity\Leasing\LeasingRequest as LeasingEntity;

class LeasingController extends AbstractController
{
    /**
     * Расчет лизинга
     *
     * Производит запрос для расчета стоимости лизинга
     *
     * @OA\Post(
     *     operationId="/client/leasing/calculate",
     *     @OA\RequestBody(
     *          @Model(type=LeasingRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт расчет по конкретным параметрам",
     *     @OA\JsonContent(
     *          ref=@Model(type=LeasingResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Client\Leasing")
     *
     * @param LeasingService $service
     * @param LeasingRequest $request
     * @return JsonResponse
     * @throws RestException
     */
    public function calculateAction(LeasingService $service, LeasingRequest $request): JsonResponse
    {
        $car = $this->getDoctrine()->getRepository(Car::class)->find($request->carId);
        if (!$car) {
            throw new NotFoundHttpException("Машина с id {$request->carId} не найдена");
        }

        $providerService = $service->getProvider($request);

        $leasingResponse = $providerService->calculate($car->getPrice(), $request->firstPayPercent, $request->term);

        return new JsonResponse($leasingResponse);
    }

    /**
     * Провайдинг запроса на лизинг
     *
     * Производит обработку запроса на лизинг
     *
     * @OA\POST(
     *     operationId="/client/leasing/provide",
     *     @OA\RequestBody(
     *          @Model(type=ProvideLeasingRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт status",
     *     @OA\JsonContent(
     *          @OA\Property (
     *              property="status",
     *              type="boolean",
     *              example=true
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Client\Leasing")
     *
     * @param ProvideLeasingRequest $request
     * @param LeasingRequestService $service
     * @return JsonResponse
     * @throws RestException
     */
    public function provideLeasingRequest(ProvideLeasingRequest $request, LeasingRequestService $service): JsonResponse
    {
        $leasingRequestEntity = new LeasingEntity();
        $leasingRequestEntity->setProvider($this->getDoctrine()->getRepository(LeasingProvider::class)->find($request->leasingProviderId));
        $leasingRequestEntity->setClient($this->getUser());
        $leasingRequestEntity->setAdvancePercent($request->firstPayPercent);
        $leasingRequestEntity->setDealerCar($this->getDoctrine()->getRepository(LeasingProvider::class)->find($request->carId));
        $leasingRequestEntity->setCalculatedMonthlyPayment($request->monthPay);
        $leasingRequestEntity->setLeasingPeriod($request->term);
        $leasingRequestEntity->setSyncAt(new \DateTime());

        $this->getDoctrine()->getManager()->persist($leasingRequestEntity);
        $this->getDoctrine()->getManager()->flush();

        assert($leasingRequestEntity instanceof LeasingEntity);
        try {
            $service->processLeasingRequest($leasingRequestEntity);
        } catch (InvalidValueException $e) {
            throw new RestException($e->getMessage());
        }
        return new JsonResponse(['status' => true]);
    }
}