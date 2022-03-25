<?php

namespace AppBundle\Service\Phone;

use AppBundle\Service\AppConfig;
use CarlBundle\Exception\CantSendSMSException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Сервис для отправки смс-сообщений пользователям
 */
class PhoneService implements PhoneServiceInterface
{
    private const FORMAT_JSON = 3;
    private const SERVICE_URL = 'https://smsc.ru/sys/';

    private ClientInterface $HttpClient;
    private LoggerInterface $Logger;

    private string $authLogin;
    private string $authPassword;

    private AppConfig $appConfig;
    private TranslatorInterface $translator;

    public function __construct(
        ClientInterface $HttpClient,
        LoggerInterface $phoneLogger,
        AppConfig $appConfig,
        string $authLogin,
        string $authPassword,
        TranslatorInterface $translator
    )
    {
        $this->HttpClient = $HttpClient;
        $this->Logger = $phoneLogger;
        $this->authLogin = $authLogin;
        $this->authPassword = $authPassword;
        $this->appConfig = $appConfig;
        $this->translator = $translator;
    }

    /**
     * Получаем информацию по номеру телефона (заодно верифицируем)
     *
     * @param string $phone
     * @return array
     *
     * @throws GuzzleException
     */
    public function getPhoneInfo(string $phone): array
    {
        $this->Logger->info(
            sprintf('Requesting info about phone %s', $phone)
        );

        $parameters = [
            'get_operator' => 1,
            'login' => $this->authLogin,
            'psw' => $this->authPassword,
            'phone' => $phone,
            'fmt' => self::FORMAT_JSON
        ];

        $Response = $this->HttpClient->request('GET', self::SERVICE_URL . '/info.php?', [
            RequestOptions::QUERY => $parameters
        ]);

        return json_decode($Response->getBody()->getContents(), true) ?? [];
    }

    /**
     * Отправляем смс на заданный номер
     *
     * @param string $phone
     * @param string $text
     *
     * @throws CantSendSMSException
     */
    public function sendSms(string $phone, string $text, ?string $appTag = null, ?array $context = []): void
    {
        $appTag ??= $this->appConfig->getAppId() ?? 'carl';
        $text = $this->translator->trans($text, $context, 'push_notifications', 'ru_'.$appTag);

        $this->Logger->info(
            sprintf('Trying to send sms to phone %s: %s', $phone, $text),
            ['phone' => $phone]
        );

        $parameters = [
            'login' => $this->authLogin,
            'psw' => $this->authPassword,
            'phones' => $phone,
            'mes' => $text,
            'charset' => 'utf-8',
            'fmt' => self::FORMAT_JSON
        ];

        if (strncmp($phone, '79', 2) !== 0) {
            $parameters['sender'] = 'SMSC.UA';
        }

//        if ($appTag === AppConfig::WL_AUDI_APP_ID) {
//            $parameters['sender'] = 'Audi City M';
//        }

        try {
            $Response = $this->HttpClient->request('GET', self::SERVICE_URL . '/send.php?', [
                RequestOptions::QUERY => $parameters
            ]);
        } catch (GuzzleException $Ex) {
            $this->Logger->info(
                $Ex->getMessage(),
                ['phone' => $phone]
            );
            throw new CantSendSMSException();
        }

        $result = json_decode($Response->getBody()->getContents(), true);

        if (!$result || isset($result['error']) || isset($result['error_code'])) {
            $this->Logger->info(
                $result['error'],
                ['phone' => $phone]
            );
            throw new CantSendSMSException();
        }
    }
}
