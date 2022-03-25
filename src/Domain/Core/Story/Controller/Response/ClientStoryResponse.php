<?php

namespace App\Domain\Core\Story\Controller\Response;

use CarlBundle\Entity\Story\Story;
use OpenApi\Annotations as OA;

/**
 * Объект ответа с историей для клиента
 */
class ClientStoryResponse extends StoryResponse
{
    public function __construct(Story $story, bool $isViewed)
    {
        parent::__construct($story);

        $this->viewed = $isViewed;
    }
}
