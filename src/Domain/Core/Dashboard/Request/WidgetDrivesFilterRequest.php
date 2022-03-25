<?php
declare(strict_types=1);

namespace App\Domain\Core\Dashboard\Request;

use AppBundle\Request\AbstractJsonRequest;
use DateTime;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class WidgetDrivesFilterRequest extends AbstractJsonRequest
{

    /**
     * @OA\Property(type="string", format="date")
     * @Assert\Date()
    */
    public $dateStart;

    /**
     * @OA\Property(type="string", format="date")
     * @Assert\Date()
     */
    public $dateEnd;

}