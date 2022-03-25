<?php


namespace App\Domain\WebSite\News\Request;


use AppBundle\Request\AbstractJsonRequest;

class ListNewsRequest extends AbstractJsonRequest
{
    public int $limit;

    public int $offset;
}