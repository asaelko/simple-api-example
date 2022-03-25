<?php

namespace App\Domain\Core\Brand\Service;

use App\Domain\Core\Brand\Controller\Builder\ClientBrandResponseBuilder;
use App\Domain\Core\Brand\Controller\Request\BrandFilterRequest;
use App\Domain\Core\Brand\Controller\Response\Client\ClientBrandResponse;
use App\Domain\Core\Brand\Repository\BrandRepository;
use App\Domain\Core\Brand\Repository\DTO\ListFilterRequest;
use AppBundle\Service\AppConfig;
use CarlBundle\Entity\Client;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;

class BrandService
{
    private Security $security;
    private ClientBrandResponseBuilder $clientResponseBuilder;
    private BrandRepository $brandRepository;
    private AppConfig $appConfig;

    public function __construct(
        Security $security,
        BrandRepository $brandRepository,
        ClientBrandResponseBuilder $clientResponseBuilder,
        AppConfig $appConfig
    )
    {
        $this->security = $security;
        $this->clientResponseBuilder = $clientResponseBuilder;
        $this->brandRepository = $brandRepository;
        $this->appConfig = $appConfig;
    }

    /**
     * Отдает список брендов и моделей в приложении в зависимости от роли пользователя
     *
     * @param BrandFilterRequest $request
     * @return array
     */
    public function getList(BrandFilterRequest $request): array
    {
        $filterRequest = new ListFilterRequest();

        $user = $this->security->getUser();
        if ($user && ($user instanceof Client)) {
            $filterRequest->cities []= $user->getCity();
        }
        $filterRequest->brands = $this->appConfig->getCurrentConfig()['brands'];

        $brands = $this->brandRepository->getList($filterRequest);

        return $this->clientResponseBuilder->build($brands);
    }

    /**
     * Отдает бренд с привязанными моделями в приложении в зависимости от роли пользователя
     *
     * @param int $brandId
     * @return ClientBrandResponse
     */
    public function get(int $brandId): ClientBrandResponse
    {
        $brand = $this->brandRepository->find($brandId);
        if (!$brand) {
            throw new NotFoundHttpException('Бренд не найден');
        }

        return $this->clientResponseBuilder->buildForBrand($brand);
    }
}
