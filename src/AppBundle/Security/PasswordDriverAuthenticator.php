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
class PasswordDriverAuthenticator extends AbstractGuardAuthenticator implements PasswordAuthenticatedInterface
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $userPasswordEncoder;

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
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return null|UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
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
     * @param mixed $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $plainPassword = $credentials['password'] ?? null;

        if (!$this->userPasswordEncoder->isPasswordValid($user, $plainPassword)) {
            throw new BadCredentialsException('некорректные учетные данные');
        }

        return true;
    }

    /**
     * Если все хорошо и аутентификация успешна
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param mixed $providerKey
     *
     * @return null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        return null;
    }

    /**
     * Если аутентифицировать не получилось
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response|null
     * @throws InvalidAuthException
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        throw new InvalidAuthException('Ошибка авторизации: ' . $exception->getMessage());
    }

    /**
     * Если требуется авторизация, но никакого токена не прислано
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new Response(null, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * У нас Stateless-сервер без пользовательских сессий, Remember me недоступен
     *
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request): bool
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
