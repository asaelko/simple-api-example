<?php

namespace App\Domain\Infrastructure\PartnerApi\Controller;

use App\Domain\Core\System\Service\Security;
use App\Domain\Infrastructure\PartnerApi\Controller\Request\PostbackDataRequest;
use App\Domain\Infrastructure\PartnerApi\Service\DataSyncService;
use App\Entity\PartnerApi\Sync\DataChangeLog;
use CarlBundle\Entity\Drive;
use CarlBundle\Entity\User;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Response\Common\BooleanResponse;
use DealerBundle\Entity\CallbackAction;
use DealerBundle\Entity\DriveOffer;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use OpenApi\Annotations as OA;

class BrandDataSyncController extends AbstractController
{
    private DataSyncService $syncService;
    private Security $security;
    private LoggerInterface $logger;

    public function __construct(
        DataSyncService $syncService,
        Security $security,
        LoggerInterface $logger
    )
    {
        $this->syncService = $syncService;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Синхронизация данных для бренда
     *
     * @OA\Get(
     *     operationId="/sync/brand/data"
     * )
     *
     * @param Request $request
     *
     * @OA\Tag(name="Sync\Brand")
     *
     * @return array
     */
    public function getData(Request $request): array
    {
        $user = $this->security->getUser();
        if (!$user || !$user->isBrandManager()) {
            throw new AccessDeniedHttpException();
        }
        /** @var User $brandManager */
        $brandManager = $user;
        $brands = $brandManager->getBrands()->toArray();

        $fromTime = $request->query->get('from', null);

        return $this->syncService->getBrandData($brands, $fromTime);
    }

    /**
     * Синхронизация данных для бренда
     *
     * @OA\Post(
     *     operationId="/sync/data/postback"
     * )
     *
     * @param PostbackDataRequest $request
     *
     * @return BooleanResponse
     * @OA\Tag(name="Sync\Brand")
     *
     */
    public function getPostback(PostbackDataRequest $request): BooleanResponse
    {
        $user = $this->security->getUser();
        if (!$user || !$user->isBrandManager()) {
            throw new AccessDeniedHttpException();
        }

        $dataMap = [
            'drives' => Drive::class,
            'callbacks' => CallbackAction::class,
            'proposals' => DriveOffer::class,
        ];

        if (!isset($dataMap[$request->type])){
            throw new InvalidValueException('Unrecognized data type');
        }

        // log data
        $changeLog = new DataChangeLog();
        $changeLog->setSourceClass(get_class($user))
            ->setSourceId($user->getId())
            ->setDataClass($dataMap[$request->type])
            ->setDataId($request->id)
            ->setDataLog($request->data);

        try {
            $this->getDoctrine()->getManager()->persist($changeLog);
            $this->getDoctrine()->getManager()->flush();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return new BooleanResponse(false);
        }

        return new BooleanResponse(true);
    }
}
