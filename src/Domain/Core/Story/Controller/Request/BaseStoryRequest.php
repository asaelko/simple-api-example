<?php

namespace App\Domain\Core\Story\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

/**
 * Базовый запрос на создание или редактирование истории
 */
class BaseStoryRequest extends AbstractJsonRequest
{
    /**
     * @Assert\Type(type="string")
     * @Assert\NotBlank(message="Заголовок истории не может быть пустым")
     */
    public string $header;

    /**
     * @Assert\Type(type="integer")
     * @Assert\NotBlank(message="Превью истории не может быть пустым")
     */
    public int $previewLinkId;

    /**
     * @Assert\Type(type="integer")
     */
    public ?int $previewHorizontalLinkId = null;

    /**
     * @Assert\Type(type="integer")
     */
    public ?int $brandId = null;

    /**
     * @Assert\Type(type="integer")
     * @Assert\NotBlank(message="Не указан флаг платформы для показа истории")
     */
    public int $showIn;

    /**
     * @Assert\Type(type="integer")
     * @Assert\NotBlank(message="Дата начала показа не может быть пустой")
     */
    public int $showStart;

    /**
     * @Assert\Type(type="integer")
     * @Assert\NotBlank(message="Дата окончания показа не может быть пустой")
     */
    public int $showEnd;

    /**
     * @Assert\NotBlank(message="В истории должен быть хотя бы один экран")
     *
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *          @OA\Property(type="integer", property="mediaId", example=1143),
     *          @OA\Property(type="integer", nullable=true, property="showTime", example=10),
     *          @OA\Property(type="string", nullable=true, property="actualText", example="Забронировать!"),
     *          @OA\Property(type="string", nullable=true, property="actualLink", example="carl://car/123")
     *     )
     * )
     */
    public array $parts;
}
