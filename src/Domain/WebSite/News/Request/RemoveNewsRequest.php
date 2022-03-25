<?php


namespace App\Domain\WebSite\News\Request;


use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class RemoveNewsRequest extends AbstractJsonRequest
{
    /**
     * @Assert\Type(type="integer", message="id должно быть целым числом")
     */
    public int $id;
}