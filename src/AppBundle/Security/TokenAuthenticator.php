<?php

namespace AppBundle\Security;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Class TokenAuthenticator
 * @package AppBundle\Security
 */
class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private ParameterBagInterface $parameterBag;

    public function __construct(
        ParameterBagInterface $parameterBag
    )
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * Подходит ли этот аутентификатор для текущего запроса
     *
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->headers->has('x-auth-token') || $request->headers->has('authorization') || $request->cookies->has('carl_auth');
    }

    /**
     * Получаем из запроса данные для аутентификации
     *
     * @param Request $request
     *
     * @return array
     */
    public function getCredentials(Request $request): array
    {
        $token = $request->headers->get('X-AUTH-TOKEN');

        // если первый токен не найден, попробуем промониторить его как Bearer
        $token = $token ?? $request->headers->get('Authorization');
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            $token = $request->cookies->get('carl_auth');
        }

        return [
            'token' => $token,
        ];
    }

    /**
     * Получаем пользователя через UserProvider
     *
     * @param array $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return null|UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $apiKey = $credentials['token'];

        if (null === $apiKey) {
            return null;
        }

        // if a User object, checkCredentials() is called
        return $userProvider->loadUserByUsername($apiKey);
    }

    /**
     * Нет необходимости проверять пароль, на входе только токен
     *
     * @param array $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * Если все хорошо и аутентификация успешна
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     *
     * @return null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    /**
     * Если аутентифицировать не получилось
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $response = new Response(null, Response::HTTP_UNAUTHORIZED);

        if ($request->cookies->has('carl_auth')) {
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

        // если токен был передан, падаем с ошибкой
        if ($request->headers->get('X-AUTH-TOKEN') || $request->headers->get('Authorization')) {
            return $response;
        }

        // иначе пользователь -- аноним
        return null;
    }

    /**
     * Если требуется авторизация, но никакого токена не прислано
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new Response(null, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * У нас Stateless-сервер без пользовательских сессий, Remember me недоступен
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }
}