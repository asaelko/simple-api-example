<?php

namespace App\Domain\Core\Subscription\Controller\Client\Request;

use AppBundle\Request\AbstractJsonRequest;
use Symfony\Component\Validator\Constraints as Assert;

class AnonymousSubscriptionRequest extends AbstractJsonRequest
{
    /**
     * Телефон клиента, на который оформляется заявка
     *
     * @var string
     *
     * @Assert\Regex(
     *     pattern="/^\d{11}$/",
     *     match=true,
     *     message="Некорректный формат номера телефона"
     * )
     */
    public $phone;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message="Пожалуйста, укажите ваш E-mail"
     * )
     *
     * @Assert\Email(
     *     message="Введен некорректный E-mail"
     * )
     */
    public $email;

    /**
     * Имя клиента
     *
     * @var string|null
     *
     * @Assert\Type("string")
     * @Assert\Length(
     *      min = 1,
     *      max = 200,
     *      minMessage = "Имя не может быть короче {{ limit }} символов",
     *      maxMessage = "Имя не может быть длиннее {{ limit }} символов"
     * )
     */
    public $firstName;

    /**
     * Фамилия клиента
     *
     * @var string|null
     *
     * @Assert\Type("string")
     * @Assert\Length(
     *      min = 1,
     *      max = 200,
     *      minMessage = "Имя не может быть короче {{ limit }} символов",
     *      maxMessage = "Имя не может быть длиннее {{ limit }} символов"
     * )
     */
    public $secondName;
}
