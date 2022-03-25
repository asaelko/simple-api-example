<?php


namespace App\Domain\Infrastructure\IpTelephony\Uis\Request;


use AppBundle\Request\AbstractJsonRequest;

class ProcessingCallActionRequest extends AbstractJsonRequest
{
    public ?string $emp = null;

    public ?string $call_id = null;

    public ?string $link = null;

    public ?string $client_phone = null;
}