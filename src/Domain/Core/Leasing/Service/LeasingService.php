<?php


namespace App\Domain\Core\Leasing\Service;


use App\Domain\Core\Leasing\CalculateLeasingInterface;
use App\Domain\Core\Leasing\Request\LeasingRequest;
use CarlBundle\Entity\Leasing\LeasingProvider;
use CarlBundle\Exception\RestException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LeasingService
{
    private ParameterBagInterface $parameterBag;

    private EntityManagerInterface $entityManager;

    public function __construct(
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param LeasingRequest $request
     * @return CalculateLeasingInterface
     * @throws RestException
     */
    public function getProvider(LeasingRequest $request): CalculateLeasingInterface
    {
        $leasingProvider = $this->getLeasingProvider($request->providerId);
        $this->validateRequest($leasingProvider, $request);
        switch ($leasingProvider->getId()) {
            case 5:
                return new GpbService($this->parameterBag);
            default:
                throw new NotFoundHttpException('Сервис для лизинг провайдера не найден');
        }
    }

    public function getLeasingProvider(int $id): LeasingProvider
    {
        $leasingProvider = $this->entityManager->getRepository(LeasingProvider::class)->find($id);
        if (!$leasingProvider) {
            throw new NotFoundHttpException('Провайдер для лизинга не найден');
        }
        assert($leasingProvider instanceof LeasingProvider);
        return $leasingProvider;
    }

    /**
     * @param LeasingProvider $provider
     * @param LeasingRequest $request
     * @throws RestException
     */
    public function validateRequest(LeasingProvider $provider, LeasingRequest $request)
    {
        if ($request->firstPayPercent > $provider->getMaxAdvancePercent() || $request->firstPayPercent < $provider->getMinAdvancePercent()) {
            throw new RestException('Не верный процент первого платежа');
        }
        if ($request->term < $provider->getMinLeasingPeriod() || $request->term > $provider->getMaxLeasingPeriod()) {
            throw new RestException('Не верный период лизинга');
        }
    }
}