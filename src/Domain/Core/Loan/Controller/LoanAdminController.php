<?php


namespace App\Domain\Core\Loan\Controller;


use App\Domain\Core\Loan\Request\GetLoanProviderRequest;
use App\Domain\Core\Loan\Request\LoanProviderListRequest;
use App\Domain\Core\Loan\Request\UpdateLoanRequest;
use App\Domain\Core\Loan\Response\LoanProviderResponse;
use CarlBundle\Entity\Loan\LoanProvider;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoanAdminController extends AbstractController
{
    /**
     * @OA\Get(
     *     operationId="/admin/loan-provider/list"
     * )
     * @OA\Parameter(
     *  name="limit",
     *  in="query",
     *  required=true,
     *  description="limit",
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *  name="offset",
     *  in="query",
     *  required=true,
     *  description="offset",
     *  @OA\Schema(type="integer")
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт список пройвайдеров с информацией о них",
     *     @OA\JsonContent(
     *          @OA\Property(
     *          property="items",
     *          type="array",
     *          @OA\Items(ref=@Model(type=LoanProviderResponse::class))
     *          ),
     *          @OA\Property(
     *          property="count",
     *          type="integer",
     *          example=10
     *          )
     *     )
     * )
     * @OA\Tag(name="Admin\Loan")
     * @param LoanProviderListRequest $request
     * @return JsonResponse
     */
    public function list(LoanProviderListRequest $request): JsonResponse
    {
        $providers = $this->getDoctrine()->getRepository(LoanProvider::class)->list($request);

        $result = array_map(
            function (LoanProvider $provider) {
                return new LoanProviderResponse($provider);
            },
            $providers
        );

        return new JsonResponse(
            [
                'items' => $result,
                'count' => $this->getDoctrine()->getRepository(LoanProvider::class)->count([])
            ]
        );
    }

    /**
     * @OA\Get(
     *     operationId="/admin/loan-provider/get"
     * )
     * @OA\Parameter(
     *  name="id",
     *  in="query",
     *  required=true,
     *  description="id провайдера",
     *  @OA\Schema(type="integer")
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт информацию по конкретному провайдеру",
     *     @OA\JsonContent(
     *          ref=@Model(type=LoanProviderResponse::class)
     *     )
     * )
     * @OA\Tag(name="Admin\Loan")
     * @param GetLoanProviderRequest $request
     * @return JsonResponse
     */
    public function getLoanProvider(GetLoanProviderRequest $request): JsonResponse
    {
        $provider = $this->getDoctrine()->getRepository(LoanProvider::class)->find($request->id);

        if (!$provider) {
            throw new NotFoundHttpException('Провайдер с таким id не найден');
        }

        return new JsonResponse(new LoanProviderResponse($provider));
    }

    /**
     * Обновить информацию по кредитному провайдеру
     *
     * @OA\Post(
     *     operationId="/admin/loan-provider/{providerId}/update"
     * )
     *
     * @OA\RequestBody(
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              ref=@Model(type=UpdateLoanRequest::class)
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт информацию по конкретному провайдеру",
     *     @OA\JsonContent(
     *          ref=@Model(type=LoanProviderResponse::class)
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Loan")
     *
     * @param int $providerId
     * @param UpdateLoanRequest $request
     * @return JsonResponse
     */
    public function update(int $providerId, UpdateLoanRequest $request): JsonResponse
    {
        $provider = $this->getDoctrine()->getRepository(LoanProvider::class)->find($providerId);

        if (!$provider) {
            throw new NotFoundHttpException('Провайдер с таким id не найден');
        }

        $provider->setFullOrganizationName($request->organizationName);

        $this->getDoctrine()->getManager()->persist($provider);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(new LoanProviderResponse($provider));
    }
}