<?php

namespace AppBundle\Security;

use CarlBundle\Exception\InvalidAuthException;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;

/**
 * Class PasswordUserAuthenticator
 * @package AppBundle\Security
 */
class PasswordUserAuthenticator extends AbstractGuardAuthenticator implements PasswordAuthenticatedInterface
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $userPasswordEncoder;

    /**
     * PasswordDriverAuthenticator constructor.
     *
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     */
    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
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
        $jsonCredentials = $request->getContent();
        try {
            return json_decode($jsonCredentials, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (JsonException $e) {
            return [];
        }
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
        $email = $credentials['email'] ?? null;

        if (null === $email) {
            return null;
        }

        return $userProvider->loadUserByUsername($email);
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
        $plainPassword = $credentials['password'] ?? null;

        if (!$this->userPasswordEncoder->isPasswordValid($user, $plainPassword)) {
            throw new BadCredentialsException();
        }

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
     * @throws InvalidAuthException
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw new InvalidAuthException();
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

    public function supports(Request $request)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }
}
