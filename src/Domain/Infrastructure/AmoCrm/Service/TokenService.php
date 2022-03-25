<?php

namespace App\Domain\Infrastructure\AmoCrm\Service;

use AmoCRM\Client\AmoCRMApiClient;
use Exception;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TokenService
{
    private ParameterBagInterface $params;
    private AmoCRMApiClient $apiClient;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->apiClient = new AmoCRMApiClient(
            $this->params->get('amo.client.id'),
            $this->params->get('amo.client.secret'),
            $this->params->get('amo.client.redirect.url')
        );
    }

    /**
     * @param $code
     */
    public function getTokenByCode($code)
    {
        try {
            $this->apiClient->setAccountBaseDomain($this->params->get('amo.client.domain'));
            $accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByCode($code);

            if (!$accessToken->hasExpired()) {
                $this->saveToken(
                    [
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $this->apiClient->getAccountBaseDomain(),
                    ]
                );
            }
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /**
     * @param $accessToken
     */
    private function saveToken($accessToken)
    {
        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            $data = [
                'accessToken' => $accessToken['accessToken'],
                'expires' => $accessToken['expires'],
                'refreshToken' => $accessToken['refreshToken'],
                'baseDomain' => $accessToken['baseDomain'],
            ];

            file_put_contents($this->params->get('amo.client.token.path'), json_encode($data));
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    public function authorization()
    {
        $authorizationUrl = $this->apiClient->getOAuthClient()->getAuthorizeUrl(
            [
                'mode' => 'post_message',
            ]
        );

        header('Location: ' . $authorizationUrl);
        die;
    }

    /**
     * @return AmoCRMApiClient
     */
    public function getApiClient(): AmoCRMApiClient
    {
        $accessToken = $this->getToken();

        $this->apiClient->setAccessToken($accessToken)
            ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
            ->onAccessTokenRefresh(
                function (AccessTokenInterface $accessToken, string $baseDomain) {
                    $this->saveToken(
                        [
                            'accessToken' => $accessToken->getToken(),
                            'refreshToken' => $accessToken->getRefreshToken(),
                            'expires' => $accessToken->getExpires(),
                            'baseDomain' => $baseDomain,
                        ]
                    );
                }
            );
        return $this->apiClient;
    }

    /**
     * @return AccessToken
     */
    private function getToken(): AccessToken
    {
        $tokenFile = $this->params->get('amo.client.token.path');

        if (file_exists($tokenFile)) {
            $accessToken = json_decode(file_get_contents($tokenFile), true);
        } else {
            $this->authorization();
        }

        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            return new AccessToken(
                [
                    'access_token' => $accessToken['accessToken'],
                    'refresh_token' => $accessToken['refreshToken'],
                    'expires' => $accessToken['expires'],
                    'baseDomain' => $accessToken['baseDomain'],
                ]
            );
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }
}