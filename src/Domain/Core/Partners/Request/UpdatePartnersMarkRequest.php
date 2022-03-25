<?php


namespace App\Domain\Core\Partners\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

class UpdatePartnersMarkRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Оценка обслуживания в числовом выражении больше или равна 0", example="10")
     * @Assert\Type(type="integer", message="Оценка должна быть целым числом")
     * @Assert\GreaterThanOrEqual(value="0", message="Оценка должна быть больше или равна 0")
     * @Assert\NotBlank(message="Оценка - обязательный параметер")
     */
    public int $mark;

    /**
     * @OA\Property(description="Коментарий для оценки, может быть пустым", example="КАИФ")
     */
    public ?string $comment;

    /**
     * @OA\Property(description="Id записи для обновления", example="1")
     * @Assert\Type(type="integer", message="Id должно быть целым числом")
     * @Assert\NotBlank(message="partnerMarkId - обязательный параметер")
     */
    public int $partnerMarkId;
}