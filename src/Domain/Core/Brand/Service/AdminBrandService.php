<?php

namespace App\Domain\Core\Brand\Service;

use App\Domain\Core\Brand\Controller\Request\AdminBrandDataRequest;
use App\Domain\Core\Brand\Controller\Request\BrandFilterRequest;
use App\Domain\Core\Brand\Factory\BrandRequestFactory;
use App\Domain\Core\Brand\Repository\BrandRepository;
use App\Domain\Core\Brand\Repository\DTO\ListFilterRequest;
use CarlBundle\Entity\Brand;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Exception\RestException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use WidgetBundle\Entity\Widget;
use WidgetBundle\Repository\WidgetRepository;

/**
 * Сервис администратора для управления брендами
 */
class AdminBrandService
{
    private BrandRequestFactory $brandFactory;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private BrandRepository $brandRepository;
    private ValidatorInterface $validator;
    private WidgetRepository $widgetRepository;

    public function __construct(
        BrandRepository $brandRepository,
        BrandRequestFactory $brandFactory,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        WidgetRepository $widgetRepository
    )
    {
        $this->brandRepository = $brandRepository;
        $this->brandFactory = $brandFactory;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->widgetRepository = $widgetRepository;
    }

    /**
     * Отдает бренд по его идентификатору
     *
     * @param int $brandId
     * @return Brand
     */
    public function resolveBrand(int $brandId): Brand
    {
        $brand = $this->entityManager->getRepository(Brand::class)->find($brandId);
        if (!$brand) {
            throw new NotFoundHttpException('Бренд не найден');
        }

        return $brand;
    }

    /**
     * Отдает список брендов в соответствии с фильтрами
     *
     * @param BrandFilterRequest $filterRequest
     * @return array
     */
    public function getList(BrandFilterRequest $filterRequest): array
    {
        return $this->brandRepository->getList(new ListFilterRequest());
    }

    /**
     * Создает новый бренд
     *
     * @param AdminBrandDataRequest $brandRequest
     * @return Brand
     * @throws InvalidValueException
     */
    public function create(AdminBrandDataRequest $brandRequest): Brand
    {
        $brand = $this->brandFactory->create($brandRequest);
        $this->validate($brand);

        $this->entityManager->persist($brand);

        $this->createMainWidget($brand);

        $this->entityManager->flush();

        return $brand;
    }

    /**
     * Обновляет существующий бренд
     *
     * @param int $brandId
     * @param AdminBrandDataRequest $brandRequest
     * @return Brand
     * @throws InvalidValueException
     */
    public function update(int $brandId, AdminBrandDataRequest $brandRequest): Brand
    {
        $brand = $this->resolveBrand($brandId);
        $this->brandFactory->update($brand, $brandRequest);
        $this->validate($brand);

        $this->entityManager->flush();

        return $brand;
    }

    /**
     * Удаляет бренд
     *
     * @param int $brandId
     * @return Brand
     * @throws RestException
     */
    public function delete(int $brandId): Brand
    {
        // todo проверки на удаление бренда
        $brand = $this->resolveBrand($brandId);

        try {
            $widgets = $this->widgetRepository->getWidgetsByBrand($brand);
            foreach ($widgets as $widget) {
                assert($widget instanceof Widget);
                $widget->removeBrand($brand);
                $this->entityManager->flush();
            }
            $this->entityManager->remove($brand);
            $this->entityManager->flush();
        } catch (Throwable $ex) {
            $this->logger->error($ex);
            throw new RestException('Не удается удалить бренд', 500);
        }

        return $brand;
    }

    /**
     * Создает и примапливает к бренду виджет
     *
     * @param Brand $brand
     */
    private function createMainWidget(Brand $brand): void
    {
        $widget = new Widget();
        $widgetId = uniqid('', false);
        $widget->setId($widgetId)
            ->setDescription($brand->getName())
            ->setBrands(new ArrayCollection([$brand]));

        $brand->setMainWidget($widget);

        $this->entityManager->persist($widget);
    }

    /**
     * Валидация сущности
     *
     * @param Brand $brand
     * @throws InvalidValueException
     */
    private function validate(Brand $brand): void
    {
        $validationErrors = $this->validator->validate($brand);

        if ($validationErrors->count() === 0) {
            return;
        }

        $firstError = $validationErrors->get(0);

        $errorMessage = $firstError->getMessage();
        if ($errorMessage === 'This value should be true.') {
            $errorMessage = sprintf(
                'Invalid %s',
                $firstError->getPropertyPath()
            );
        }
        throw new InvalidValueException($errorMessage);
    }
}
