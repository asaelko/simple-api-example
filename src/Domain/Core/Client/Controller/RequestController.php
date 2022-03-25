<?php

namespace App\Domain\Core\Client\Controller;

use App\Domain\Core\Client\Service\RequestService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class RequestController extends AbstractController
{
    private RequestService $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * История запросов клиентов
     *
     * @OA\Get(
     *     operationId="/client/requests/list"
     * )
     *
     * @param Request $request
     * @param string  $type
     *
     * @OA\Tag(name="Client\Requests")
     *
     * @return array
     */
    public function getList(Request $request, string $type): array
    {
        $isArchived = $request->query->get('archived', false);
        switch ($type) {
            case 'drives':
                return $this->requestService->getDrives($isArchived);
            case 'offers':
                return $this->requestService->getRequests($isArchived);
            case 'all':
            default:
                return $this->requestService->getAll($isArchived);
        }
    }
}