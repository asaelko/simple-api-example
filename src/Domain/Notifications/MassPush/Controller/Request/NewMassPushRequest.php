<?php

namespace App\Domain\Notifications\MassPush\Controller\Request;

use AppBundle\Request\AbstractJsonRequest;
use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Запрос на создание новой массовой пуш-рассылки
 */
class NewMassPushRequest extends AbstractJsonRequest
{
    /**
     * Заголовок масспуша
     *
     * @var string|null
     *
     * @Assert\Type(type="string", message="Заголовок масспуша должен быть строкой")
     */
    public ?string $title = null;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Текст масспуша не может отсутствовать")
     * @Assert\Type(type="string", message="Текст масспуша должен быть строкой")
     */
    public string $text;

    /**
     * @var string|null
     *
     * @Assert\Type(type="string", message="Диплинк масспуша должен быть строкой")
     */
    public ?string $link = null;

    /**
     * @var int|null
     *
     * @Assert\DateTime(format="U", message="Дата передана в неверном формате")
     */
    public ?int $sendDate = null;

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @return DateTime
     */
    public function getSendDate(): DateTime
    {
        return $this->sendDate ? DateTime::createFromFormat('U', $this->sendDate) : new DateTime();
    }
}
