<?php

namespace App\Domain\Core\Web\Controller\Client;

use App\Domain\Core\Web\Controller\Client\Request\ContactUsRequest;
use AppBundle\Service\Mail\Mail;
use AppBundle\Service\Mail\MailService;
use CarlBundle\Response\Common\BooleanResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PartnerController extends AbstractController
{
    private MailService $mailService;

    public function __construct(
        MailService $mailService
    )
    {
        $this->mailService = $mailService;
    }

    /**
     * Отправка заявки из формы обратной связи
     *
     * @OA\Post(
     *     operationId="web/partners/contact-us",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  ref=@Model(type=ContactUsRequest::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт результат запроса",
     *     @Model(type=BooleanResponse::class)
     * )
     *
     * @OA\Tag(name="Web\Partners")
     *
     * @param ContactUsRequest $request
     *
     * @return BooleanResponse
     */
    public function contactUs(ContactUsRequest $request): BooleanResponse
    {
        $company = $request->company ?: '-';
        if ($request->position) {
            $company .= ', ' . $request->position;
        }

        $mail = new Mail();
        $mail->setTextContent(<<<MAIL
            ФИО: {$request->name}
            Компания: {$company}
            Телефон: {$request->phone}
            Почта: {$request->email}
MAIL
);
        $mail->setSubject('Новая заявка из формы обратной связи');
        $mail->addRecipient([
            'email' => 'welcome@carl-drive.ru'
        ]);
        $this->mailService->sendEmail($mail);

        return new BooleanResponse(true);
    }
}