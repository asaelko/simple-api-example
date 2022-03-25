<?php


namespace App\Domain\WebSite\News\Request;

use OpenApi\Annotations as OA;

class UpdateNewsRequest extends CreateNewsRequest
{
    /**
     * @OA\Property(description="id новости")
     */
    public int $id;
}