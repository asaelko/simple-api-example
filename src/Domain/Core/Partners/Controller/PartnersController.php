<?php

namespace App\Domain\Core\Partners\Controller;

use App\Domain\Core\Partners\Controller\Response\PartnerResponse;
use App\Domain\Core\Partners\Repository\PartnersRepository;
use CarlBundle\Entity\Partner;
use CarlBundle\Exception\InvalidValueException;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Контроллер клиента для получения информации о партнерах
 */
class PartnersController extends AbstractController
{
    private PartnersRepository $repository;

    public function __construct(
        PartnersRepository $repository
    )
    {
        $this->repository = $repository;
    }

    /**
     * Отдает список партнеров для заданного блока
     *
     * @OA\Get(operationId="client/partners/list")
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернет список партнеров для отображения",
     *     @OA\JsonContent(
     *           @OA\Property(type="array", @OA\Items(ref=@Model(type=PartnerResponse::class)))
     *     )
     * )
     *
     * @param string $category
     *
     * @return array
     * @throws InvalidValueException
     *
     * @OA\Tag(name="Client\Partners")
     */
    public function getList(string $category): array
    {
        $showIn = null;
        switch ($category) {
            case 'drive':
                $showIn = Partner::SHOW_IN_SECTION_TEST_DRIVES;
                break;
            case 'buy':
                $showIn = Partner::SHOW_IN_SECTION_BUY;
                break;
        }
        if ($showIn === null) {
            throw new InvalidValueException('Выбрана неверная категория');
        }

        $partners = $this->repository->getByCategory($showIn);

        return array_map(static fn(Partner $partner) => new PartnerResponse($partner), $partners);
    }
}
