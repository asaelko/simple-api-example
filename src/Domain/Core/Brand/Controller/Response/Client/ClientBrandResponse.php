<?php

namespace App\Domain\Core\Brand\Controller\Response\Client;

use App\Domain\Core\Brand\Controller\Response\BaseBrandResponse;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\Model\Model;
use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;

/**
 * Объект бренда, передающийся на мобильные клиенты
 */
class ClientBrandResponse extends BaseBrandResponse
{
    /**
     * @OA\Property(
     *     type="array",
     *     description="Массив типов кузовов, имеющихся у бренда",
     *     @OA\Items(ref=@DocModel(type=BodyTypeResponse::class))
     * )
     *
     * @var array|BodyTypeResponse[]
     */
    public array $bodyTypes = [];

    /**
     * @param Brand $brand
     * @param array $bodyTypes
     * @param array|Model[] $models
     */
    public function __construct(Brand $brand, array $bodyTypes, array $models)
    {
        parent::__construct($brand);

        $typedModels = [];
        foreach ($models as $modelData) {
            $typedModels[$modelData['body_type']] ??= [];
            $typedModels[$modelData['body_type']][] = $modelData;
        }

        foreach ($bodyTypes as $bodyType) {
            $this->bodyTypes[] = new BodyTypeResponse($bodyType, $typedModels[$bodyType['id']] ?? []);
        }
    }
}
