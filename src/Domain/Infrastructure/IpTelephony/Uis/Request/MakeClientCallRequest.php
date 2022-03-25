<?php


namespace App\Domain\Infrastructure\IpTelephony\Uis\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class MakeClientCallRequest extends AbstractJsonRequest
{
    /**
     * @Assert\Type(type="string", message="Поле phone должно быть текстовым в  формате 7ХХХХХХХХХ")
     * @Assert\NotBlank(message="Поле phone обязательное")
     */
    public string $phone;

    /**
     * @Assert\Type(type="boolean", message="Звонок сразу в КЦ")
     */
    public ?bool $toCallCanter = false;
}
