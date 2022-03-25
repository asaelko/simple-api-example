<?php

namespace App\Domain\Core\Brand\Controller\Response\Client;

use Nelmio\ApiDocBundle\Annotation\Model as DocModel;
use OpenApi\Annotations as OA;

/**
 * Тело ответа типа кузова для клиента
 */
class BodyTypeResponse
{
    /**
     * Уникальный идентификатор типа кузова
     *
     * @OA\Property(example=1)
     */
    public int $id;

    /**
     * Наименование типа кузова
     *
     * @OA\Property(example="Седан")
     */
    public string $text;

    /**
     * Изображение типа кузова, специфичное для бренда
     *
     * @OA\Property(ref=@DocModel(type=PhotoResponse::class))
     */
    public ?PhotoResponse $photo = null;

    /**
     * Модели бренда, относящиеся к данному типу кузова
     *
     * @var array|ModelResponse[]
     *
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@DocModel(type=ModelResponse::class))
     * )
     */
    public array $models;

    public function __construct(array $bodyTypeData, array $models)
    {
        $this->id = $bodyTypeData['id'];
        $this->text = $bodyTypeData['text'];
        if (isset($bodyTypeData['photo'])) {
            $this->photo = new PhotoResponse($bodyTypeData['photo']);
        }

        foreach ($models as $model) {
            $this->models[] = new ModelResponse($model);
        }
    }
}
