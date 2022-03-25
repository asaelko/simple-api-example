<?php
namespace App\Domain\Infrastructure\IpTelephony\Uis\Request;

use AppBundle\Request\AbstractJsonRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class MakeDriverCallRequest extends AbstractJsonRequest
{
    /**
     * @OA\Property(description="Id поедки для котой надо создать звонок")
     * @Assert\Type(type="integer", message="driveId должно быть целым числом")
     * @Assert\NotBlank(message="driveId обязательное поле")
     */
    public int $driveId;
}