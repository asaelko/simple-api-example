<?php

namespace App\Domain\Core\Client\Controller;

use App\Domain\Core\Client\Controller\Request\CheckPhoneVerificationRequest;
use App\Domain\Core\Client\Controller\Request\SendEmailVerificationRequest;
use App\Domain\Core\Client\Controller\Request\SendPhoneVerificationCodeRequest;
use App\Domain\Core\Client\Controller\Request\UpdatePushTokenRequest;
use App\Domain\Core\Client\Exception\TooEarlyForCodeResendApiException;
use App\Domain\Core\Client\Service\EmailVerificationService;
use App\Domain\Core\Client\Service\PhoneVerificationService;
use CarlBundle\Entity\Client;
use CarlBundle\Exception\CantSendSMSException;
use CarlBundle\Exception\EarlyForResentException;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Exception\RestException;
use CarlBundle\Service\ClientService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Контроллер для работы с профилем клиента
 */
class ProfileController extends AbstractController
{
    private ClientService $clientService;
    private PhoneVerificationService $phoneVerificationService;
    private EmailVerificationService $emailVerificationService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ClientService $clientService,
        PhoneVerificationService $phoneVerificationService,
        EmailVerificationService $emailVerificationService,
        EntityManagerInterface $entityManager
    )
    {
        $this->clientService = $clientService;
        $this->phoneVerificationService = $phoneVerificationService;
        $this->emailVerificationService = $emailVerificationService;
        $this->entityManager = $entityManager;
    }

    /**
     * Запрос смены номера телефона у клиента
     *
     * Смена номера телефона может быть запрещена, если, например, указанный номер телефона принадлежит
     *      другому пользователю, или у текущего пользователя есть активные незавершенные поездки
     *
     * @OA\Post(
     *     operationId="profile/sendCodeForChangePhone",
     *     @OA\RequestBody(
     *          @Model(type=SendPhoneVerificationCodeRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт идентификатор сессии подтверждения нового номера телефона",
     *     @OA\JsonContent(
     *        @OA\Property(property="id", type="string", example="186f9688-395b-11eb-9c40-faffc2328f70")
     *     )
     *   )
     * )
     *
     * @OA\Response(
     *     response=411, description="Ошибка частоты запроса кода; код можно запрашивать не чаще, чем раз в 30 секунд",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Слишком рано для повторного запроса кода")
     *      )
     *   )
     * )
     *
     * @OA\Response(
     *     response=461, description="У пользователя есть активные тест-драйвы",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Нельзя изменить email или телефон, пока вы ожидаете тест-драйва")
     *      )
     *   )
     * )
     *
     * @OA\Response(
     *     response=471, description="Ошибка отправки смс-сообщения; номер может быть некорректным или на счету закончились деньги",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Не удается отправить смс на указанный номер")
     *      )
     *   )
     * )
     *
     * @OA\Tag(name="Client\Profile")
     *
     * @param SendPhoneVerificationCodeRequest $codeRequest
     *
     * @return JsonResponse
     * @throws CantSendSMSException
     * @throws InvalidValueException
     * @throws RestException
     * @throws TooEarlyForCodeResendApiException
     */
    public function sendCodeForChangePhone(
        SendPhoneVerificationCodeRequest $codeRequest
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!($user instanceof Client)) {
            throw new RestException('Попытка сменить номер телефона пользователя не от пользователя');
        }
        // todo переделать, хорошо бы это чекать в Voter-е
        $this->clientService->validateUserForUpdateAfterDrive($user, ['phone' => $codeRequest->phone]);

        $phoneVerificationEntity = $this->phoneVerificationService->sendVerificationCode($codeRequest->phone);

        return new JsonResponse(['id' => $phoneVerificationEntity->getId()]);
    }

    /**
     * Подтверждение смены номера телефона у клиента
     *
     * @OA\Post(
     *     operationId="auth/changePhoneAfterVerification",
     *     @OA\RequestBody(
     *          @Model(type=CheckPhoneVerificationRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Изменит телефон у пользователя и вернет результат операции",
     *     @OA\JsonContent(
     *        @OA\Property(property="status", type="bool", example=true)
     *     )
     *   )
     * )
     *
     * @OA\Response(
     *     response=404, description="Не найден запрос на подтверждение номера",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Не найден запрос на подтверждение телефона")
     *      )
     *   )
     * )
     *
     * @OA\Response(
     *     response=405, description="Попытка сменить номер телефона пользователя не от пользователя",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Попытка сменить номер телефона пользователя не от пользователя")
     *      )
     *   )
     * )
     *
     * @OA\Response(
     *     response=410, description="Ошибка об истечении срока действия сессии подтверждения номера",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Время действия кода подтверждения истекло")
     *      )
     *   )
     * )
     *
     * @OA\Response(
     *     response=461, description="У пользователя есть активные тест-драйвы",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Нельзя изменить email или телефон, пока вы ожидаете тест-драйв")
     *      )
     *   )
     * )
     *
     * @OA\Response(
     *     response=470, description="Код подтверждения не соответствует отправленному",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Неверный код подтверждения")
     *      )
     *   )
     * )
     *
     * @OA\Response(
     *     response=472, description="Номер телефона уже используется",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Номер телефона уже используется")
     *      )
     *   )
     * )
     *
     * @OA\Response(
     *     response=473, description="Ошибка валидации переданных данных",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Поле phone обязательно")
     *      )
     *   )
     * )
     *
     * @OA\Response(
     *     response=474, description="Номер телефона пользователя уже был изменен",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Номер телефона пользователя уже был изменен")
     *      )
     *   )
     * )
     *
     * @OA\Tag(name="Client\Profile")
     *
     * @param CheckPhoneVerificationRequest $request
     *
     * @return JsonResponse
     *
     * @throws InvalidValueException
     * @throws RestException
     */
    public function changePhoneAfterVerification(
        CheckPhoneVerificationRequest $request
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!($user instanceof Client)) {
            throw new RestException('Попытка сменить номер телефона пользователя не от пользователя', 405);
        }

        $phoneVerificationEntity = $this->phoneVerificationService
            ->checkVerificationCode($request->verificationId, $request->code);

        $this->clientService->validateUserForUpdateAfterDrive($user, ['phone' => $phoneVerificationEntity->getPhoneNumber()]);

        $oldUser = $this->getDoctrine()->getRepository(Client::class)->findOneBy(['phone' => $phoneVerificationEntity->getPhoneNumber()]);
        if ($oldUser && $oldUser->getId() !== $user->getId()) {
            throw new RestException('error.phone_verification.already_used', 472);
        }

        $user->setPhone($phoneVerificationEntity->getPhoneNumber());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(['status' => true]);
    }

    /**
     * Запрос на указание/смену почтового адреса для клиента
     *
     * @OA\Post(
     *     operationId="profile/sendEmailVerification",
     *     @OA\RequestBody(
     *          @Model(type=SendEmailVerificationRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Отправит письмо с подтверждением адреса на указанный почтовый ящик",
     *     @OA\JsonContent(
     *        @OA\Property(property="status", type="bool", example=true)
     *     )
     * )
     *
     *
     * @OA\Response(
     *     response=461, description="Этот email уже подтвержден",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Этот email уже подтвержден")
     *     )
     * )
     *
     * @OA\Response(
     *     response=472, description="Повторный перезапрос подтверждения почты",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="С последнего запроса подтверждения прошло менее 60 секунд"),
     *        @OA\Property(property="data",type="array", @OA\Items(
     *          @OA\Property(property="resendIn", type="int", example=23, description="Время до возможности повторной отправки")
     *        ))
     *     )
     * )
     *
     * @OA\Tag(name="Client\Profile")
     *
     * @param SendEmailVerificationRequest $emailVerificationRequest
     * @return JsonResponse
     * @throws InvalidValueException
     * @throws EarlyForResentException
     */
    public function processRequestForChangeEmail(SendEmailVerificationRequest $emailVerificationRequest): JsonResponse
    {
        $this->emailVerificationService->processVerificationRequest($emailVerificationRequest);
        return new JsonResponse(['status' => true]);
    }

    /**
     * Обновляем пуш-токен клиента
     *
     * @OA\Post(
     *     operationId="client/patch/phone-token",
     *     @OA\RequestBody(
     *          @Model(type=UpdatePushTokenRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Обновит пуш-токен пользователя",
     *     @OA\JsonContent(
     *        @OA\Property(property="status", type="bool", example=true)
     *     )
     *   )
     * )
     *
     * @OA\Tag(name="Client\Profile")
     *
     * @param UpdatePushTokenRequest $pushTokenRequest
     * @return JsonResponse
     */
    public function updatePushToken(UpdatePushTokenRequest $pushTokenRequest): JsonResponse
    {
        $user = $this->getUser();
        assert($user instanceof Client);

        $user->setPushToken($pushTokenRequest->token);
        if ($pushTokenRequest->mobileOs) {
            $user->setMobileOS($pushTokenRequest->mobileOs);
        }
        if ($pushTokenRequest->phoneModel) {
            $user->setPhoneModel($pushTokenRequest->phoneModel);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(['status' => true]);
    }
}
