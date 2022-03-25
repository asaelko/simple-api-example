<?php

namespace App\Domain\Core\Equipment\Controller;

use App\Domain\Core\Equipment\Controller\Request\NewEquipmentMediaRequest;
use App\Entity\Equipment\EquipmentMedia;
use App\Repository\Equipment\EquipmentMediaRepository;
use CarlBundle\Entity\Equipment;
use CarlBundle\Entity\Media\Media;
use CarlBundle\Response\Common\BooleanResponse;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Управление медиа-контентом комплектаций
 */
class EquipmentMediaController extends AbstractController
{
    private EquipmentMediaRepository $repository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        EquipmentMediaRepository $repository
    )
    {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
    }

    /**
     * Получение медиа, прикрепленных к комплектации
     *
     * @OA\Get(
     *     operationId="/admin/equipment/media"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Объект добавленного медиаконтента",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="id", type="int", example=1),
     *                  @OA\Property(property="category", type="string", example="Видеообзор"),
     *                  @OA\Property(property="media", type="object")
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Equipments\Media")
     */
    public function showAction(int $equipmentId): array
    {
        $media = $this->repository->findBy([
            'equipment' => $equipmentId
        ]);

        return array_map(static fn(EquipmentMedia $equipmentMedia) => [
            'id' => $equipmentMedia->getId(),
            'category' => $equipmentMedia->getCategory(),
            'media' => $equipmentMedia->getMedia()
        ], $media);
    }

    /**
     * Добавление медиа к комплектации
     *
     * @OA\Post(
     *     operationId="/admin/equipment/media/add",
     *     @OA\RequestBody(
     *         @Model(type=NewEquipmentMediaRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Объект добавленного медиаконтента",
     *     @OA\JsonContent(
     *          @OA\Property(property="id", type="int", example=1),
     *          @OA\Property(property="category", type="string", example="Видеообзор"),
     *          @OA\Property(property="media", type="object")
     *     )
     * )
     *
     * @OA\Tag(name="Admin\Equipments\Media")
     */
    public function addAction(NewEquipmentMediaRequest $request, int $equipmentId): array
    {
        /** @var Equipment $equipment */
        $equipment = $this->entityManager->getRepository(Equipment::class)->find($equipmentId);
        if (!$equipment) {
            throw new NotFoundHttpException('Комплектация не найдена');
        }

        /** @var Media $media */
        $media = $this->entityManager->getRepository(Media::class)->find($request->mediaId);
        if (!$media) {
            throw new NotFoundHttpException('Медиаконтент не найден');
        }

        $equipmentMedia = new EquipmentMedia();
        $equipmentMedia->setEquipment($equipment)
            ->setMedia($media)
            ->setCategory($request->category);

        $this->entityManager->persist($equipmentMedia);
        $this->entityManager->flush();

        return [
            'id' => $equipmentMedia->getId(),
            'category' => $equipmentMedia->getCategory(),
            'media' => $equipmentMedia->getMedia()
        ];
    }

    /**
     * Удаление медиа из комплектации
     *
     * @OA\Delete(
     *     operationId="/admin/equipment/media/delete"
     * )
     *
     * @OA\Tag(name="Admin\Equipments\Media")
     */
    public function deleteAction(int $equipmentId, int $equipmentMediaId): BooleanResponse
    {
        $equipmentMedia = $this->repository->find($equipmentMediaId);
        if (!$equipmentMedia) {
            throw new NotFoundHttpException('Контент не найден');
        }

        $this->entityManager->remove($equipmentMedia);
        $this->entityManager->flush();

        return new BooleanResponse(true);
    }
}
