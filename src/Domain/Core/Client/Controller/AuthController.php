<?php

namespace App\Domain\Core\Client\Controller;

use App\Domain\Core\Client\Controller\Request\CheckPhoneVerificationRequest;
use App\Domain\Core\Client\Controller\Request\SendPhoneVerificationCodeRequest;
use App\Domain\Core\Client\Controller\Response\ClientAuthApiResponse;
use App\Domain\Core\Client\Exception\ExpiredVerificationRequestApiException;
use App\Domain\Core\Client\Exception\InvalidVerificationCodeApiException;
use App\Domain\Core\Client\Exception\MissingVerificationRequestApiException;
use App\Domain\Core\Client\Exception\TooEarlyForCodeResendApiException;
use App\Domain\Core\Client\Service\ClientAuthService;
use App\Domain\Core\Client\Service\PhoneVerificationService;
use App\Domain\Core\Geo\Service\GeoDataService;
use CarlBundle\Entity\Client;
use CarlBundle\Exception\CantSendSMSException;
use CarlBundle\Exception\ClientIsBanLoginException;
use CarlBundle\Request\Client\ClientRegistrationRequest;
use CarlBundle\Service\Client\RegistrationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Контроллер для работы с профилем клиента
 */
class AuthController extends AbstractController
{
    private PhoneVerificationService $phoneVerificationService;
    private RegistrationService $registrationService;
    private EntityManagerInterface $entityManager;
    private ClientAuthService $authService;
    private ParameterBagInterface $parameterBag;
    private GeoDataService $geoDataService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PhoneVerificationService $phoneVerificationService,
        RegistrationService $registrationService,
        ClientAuthService $authService,
        ParameterBagInterface $parameterBag,
        GeoDataService $geoDataService
    )
    {
        $this->phoneVerificationService = $phoneVerificationService;
        $this->registrationService = $registrationService;
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->parameterBag = $parameterBag;
        $this->geoDataService = $geoDataService;
    }

    /**
     * Отправка кода подтверждения на телефон
     *
     * Метод получает телефон, который необходимо подтвердить,
     * отправляет на него смс и отдает сессию верификации этого кода.
     *
     * Сессия живет 5 минут, после чего при попытке её использования
     * для подтверждения номера на указанный телефон будет отправлен новый код
     *
     * @OA\Post(
     *     operationId="auth/sendPhoneVerificationCode",
     *     @OA\RequestBody(
     *          @Model(type=SendPhoneVerificationCodeRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200, description="Вернет код сессии подтверждения номера телефона",
     *     @OA\JsonContent(
     *        @OA\Property(property="id", type="string", example="186f9688-395b-11eb-9c40-faffc2328f70")
     *     )
     * )
     *
     * @OA\Response(
     *     response=411, description="Ошибка частоты запроса кода; код можно запрашивать не чаще, чем раз в 30 секунд",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Слишком рано для повторного запроса кода")
     *     )
     * )
     *
     * @OA\Response(
     *     response=471, description="Ошибка отправки смс-сообщения; номер может быть некорректным или на счету закончились деньги",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Не удается отправить смс на указанный номер")
     *     )
     * )
     *
     * @OA\Response(
     *     response=473, description="Ошибка валидации переданных данных",
     *     @OA\JsonContent(
     *        @OA\Property(property="error",type="string",example="Поле phone обязательно")
     *     )
     * )
     *
     * @OA\Tag(name="Client\Auth")
     *
     * @param SendPhoneVerificationCodeRequest $request
     * @return JsonResponse
     *
     * @throws CantSendSMSException
     * @throws TooEarlyForCodeResendApiException
     */
    public function sendPhoneVerificationCodeAction(
        SendPhoneVerificationCodeRequest $request
    ): JsonResponse
    {
        $phoneVerificationEntity = $this->phoneVerificationService->sendVerificationCode($request->phone);
        return new JsonResponse(['id' => $phoneVerificationEntity->getId()]);
    }

    /**
     * Проверка кода подтверждения телефона
     *
     * Метод позволяет проверить телефон пользователя по предварительно отправленной смс.
     * В теле необходимо передать полученный ранее токен верификации номера и код, введенный пользователем.
     *
     * Если получена ошибка 410 (срок действия кода подтверждения вышел) –
     *      пользователю будет отправлен новый код в рамках действия той же сессии, а срок действия сессии продлен еще на 5 минут
     *
     * Если пользователя в сервисе прежде не существовало, будет создан и возвращен новый пользователь.
     *
     * @OA\Post(
     *     operationId="auth/checkPhoneVerificationCode",
     *     @OA\RequestBody(
     *          @Model(type=CheckPhoneVerificationRequest::class)
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт авторизационный токен и объект клиента",
     *     @OA\JsonContent(
     *        @OA\Property(
     *              property="token",
     *              type="strig",
     *              example="186f9688-395b-11eb-9c40-faffc2328f70"
     *        ),
     *        @OA\Property(
     *              property="client",
     *              type="object",
     *              ref=@Model(type=ClientAuthApiResponse::class)
     *        )
     *     )
     * )
     *
     * @OA\Response(
     *     response=404, description="Не найден запрос на подтверждение номера",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Не найден запрос на подтверждение телефона")
     *     )
     * )
     *
     * @OA\Response(
     *     response=410, description="Ошибка об истечении срока действия сессии подтверждения номера",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Время действия кода подтверждения истекло")
     *     )
     * )
     *
     * @OA\Response(
     *     response=470, description="Код подтверждения не соответствует отправленному",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Неверный код подтверждения")
     *     )
     * )
     *
     * @OA\Response(
     *     response=473, description="Ошибка валидации переданных данных",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Поле code обязательно")
     *     )
     * )
     *
     * @OA\Response(
     *     response=530, description="Клиент был забанен",
     *     @OA\JsonContent(
     *        @OA\Property(property="error", type="string", example="Ошибка доступа")
     *     )
     * )
     *
     * @OA\Tag(name="Client\Auth")
     *
     * @param CheckPhoneVerificationRequest $request
     * @return JsonResponse
     *
     * @throws CantSendSMSException
     * @throws ExpiredVerificationRequestApiException
     * @throws InvalidVerificationCodeApiException
     * @throws MissingVerificationRequestApiException
     * @throws ClientIsBanLoginException
     */
    public function checkPhoneVerificationCodeAction(
        CheckPhoneVerificationRequest $request
    ): JsonResponse
    {
        $phoneVerificationEntity = $this->phoneVerificationService->checkVerificationCode($request->verificationId, $request->code);

        $client = $this->authService->tryAuthBy([
            'phone' => $phoneVerificationEntity->getPhoneNumber()
        ]);

        if (!$client) {
            $crr = new ClientRegistrationRequest();
            $crr->phone = $phoneVerificationEntity->getPhoneNumber();
            $client = $this->registrationService->createNewClientWithoutSendSms($crr);
            $this->authService->updateToken($client);
        }

        $city = $client->getCity() ?? $this->geoDataService->getDefaultCity();

        $response = new JsonResponse(['client' => new ClientAuthApiResponse($client, $city), 'token' => $client->getToken()]);
        $response->headers->setCookie(
            Cookie::create(
                'carl_auth',
                $client->getToken(),
                (new DateTime)->getTimestamp() + 3600 * 24 * 7,
                '/',
                $this->parameterBag->get('cookie.site'),
                true,
                true,
                false,
                Cookie::SAMESITE_NONE
            )
        );

        return $response;
    }

    /**
     * Выход пользователя из приложения
     *
     * @OA\Get(
     *     operationId="client/logout"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Подтверждение успешного логаута",
     *     @OA\JsonContent(
     *        @OA\Property(property="status", type="bool", example=true)
     *     )
     * )
     *
     * @OA\Tag(name="Client\Auth")
     */
    public function logout(): JsonResponse
    {
        $client = $this->getUser();
        assert($client instanceof Client);

        $client->setToken(null)
            ->setPushToken(null)
            ->setLogOutDate(new DateTime());

        $this->entityManager->flush();

        $response = new JsonResponse(['status' => true]);
        $response->headers->clearCookie(
            'carl_auth',
            '/',
            $this->parameterBag->get('cookie.site'),
            true,
            true,
            Cookie::SAMESITE_NONE
        );

        return $response;
    }
}
