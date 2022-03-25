<?php

namespace App\Domain\Core\ExperienceCenters\Controller;

use App\Domain\Core\ExperienceCenters\Request\BrandCreateCenterRequest;
use App\Domain\Core\ExperienceCenters\Response\AdminGetClientsRequestsResponse;
use App\Domain\Core\ExperienceCenters\Response\BrandGetCenterResponse;
use App\Entity\ExperienceCenter;
use App\Entity\ExperienceCenterSchedule;
use CarlBundle\Entity\User;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Контроллер для управления экспириенс-центров менеджером бренда
 */
class BrandController extends AbstractController
{
    /**
     * Просмотр подтвержденных слотов
     *
     * Вернёт слоты и запроси на запись, если они есть
     *
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт слоты и запросы если есть",
     *     @OA\JsonContent(
     *          @OA\Property (
     *              property="items",
     *              type="array",
     *              @OA\Items(
     *                  ref=@Model(type=AdminGetClientsRequestsResponse::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Brand\ExperienceCenter")
     *
     * @return JsonResponse
     */
    public function getRequests(): JsonResponse
    {
        $manager = $this->getUser();
        assert($manager instanceof User);
        $brands = $manager->getBrands();

        $centers = $this->getDoctrine()->getManager()->getRepository(ExperienceCenter::class)->findBy(
            [
                'brand' => $brands->toArray(),
            ]
        );

        $slots = $this->getDoctrine()->getManager()->getRepository(ExperienceCenterSchedule::class)->findBy(
            [
                'experienceCenter' => $centers,
                'isBooked' => true
            ]
        );

        $result = array_map(
            function (ExperienceCenterSchedule $schedule) {
                return new AdminGetClientsRequestsResponse($schedule, $schedule->getScheduleRequest()->toArray());
            },
            $slots
        );

        return new JsonResponse(['items' => $result]);
    }
}
