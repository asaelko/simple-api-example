<?php

namespace App\Domain\Core\Dashboard\Service;

use App\Domain\Core\Dashboard\Repository\FilterRepository;
use App\Domain\Core\System\Service\Security;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\Dealer;
use CarlBundle\Entity\User;

/**
 * Сервис, отдающий фильтры для дешборда в зависимости от роли текущего пользователя
 */
class FilterService
{
    private Security $security;
    private FilterRepository $filterRepository;

    public function __construct(
        Security $security,
        FilterRepository $filterRepository
    )
    {
        $this->security = $security;
        $this->filterRepository = $filterRepository;
    }

    /**
     * В зависимости от роли отдает список виджетов, по которым были тест-драйвы, с количеством проведенных тест-драйвов
     *
     * @return array
     */
    public function getWidgetWithDrivesFilter(): array
    {
        $user = $this->security->getUser();
        if (!$user || !($user instanceof User)) {
            return [];
        }

        $widgets = [];
        if ($user->isDealerManager()) {
            $widgets = array_map(static fn(Dealer $dealer) => $dealer->getWidgetCode(), $user->getDealers()->toArray());
        }

        if ($user->isBrandManager()) {
            $widgets = array_map(static fn(Brand $brand) => $brand->getWidgetCode(), $user->getBrands()->toArray());
        }

        return $this->filterRepository->getWidgetWithDrivesFilter($widgets);
    }
}
