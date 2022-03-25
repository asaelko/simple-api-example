<?php

namespace AppBundle\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Глобальные настройки приложения для инжекта в сервисы
 */
class AppConfig
{
    public const WL_MAIN = 'main';

    public const APPLICATIONS = [self::WL_MAIN];

    /**
     * @var string
     */
    private $env;

    /**
     * @var string|null
     */
    private $appId;
    /**
     * @var string
     */
    private $host;
    /**
     * @var string
     */
    private $websiteHost;

    /**
     * @var array
     */
    private $wlConfig;

    public function __construct(
        ParameterBagInterface $parameterBag
    )
    {
        $this->env = $parameterBag->get('env_key');
        $this->host = $parameterBag->get('host');
        $this->websiteHost = $parameterBag->get('website_domain');

        $this->wlConfig = $parameterBag->get('wl');
    }

    /**
     * Проверяем, в тестовом ли окружении находимся
     *
     * @return bool
     */
    public function isTest(): bool {
        return $this->env === 'test';
    }

    /**
     * Проверяем, не находимся ли мы в стейдж-окружении
     *
     * @return bool
     */
    public function isStage(): bool {
        return $this->env === 'stage';
    }

    /**
     * Проверяем, не находимся ли мы в прод-окружении
     *
     * @return bool
     */
    public function isProd(): bool
    {
        return $this->env === 'prod';
    }

    /**
     * @return string|null
     */
    public function getAppId(): ?string
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     * @return AppConfig
     */
    public function setAppId(string $appId): AppConfig
    {
        $this->appId = mb_strtolower($appId);
        return $this;
    }

    /**
     * Получаем текущий хост сайта
     *
     * @return string
     */
    public function getWebsiteHost(): string
    {
        return $this->websiteHost;
    }

    /**
     * Получаем текущий хост апи
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Получаем типы доступных WL приложений
     *
     * @return array
     */
    public function getWlTypes(): array
    {
        return array_keys($this->wlConfig);
    }

    /**
     * Получаем конфиг для WL
     *
     * @param string|null $appTag
     * @return array
     */
    public function getWlConfig(?string $appTag): array
    {
        return $this->wlConfig[$appTag ?? self::WL_MAIN] ?? [];
    }

    /**
     * @return array
     */
    public function getCurrentConfig(): array
    {
        return $this->getWlConfig($this->getAppId());
    }
}
