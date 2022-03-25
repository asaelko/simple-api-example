<?php

namespace App\Domain\Core\LongDrive\Controller\Admin\Factory;

use App\Domain\Core\LongDrive\Controller\Admin\Request\CreateLongDriveStockModelRequest;
use App\Entity\LongDrive\LongDriveModel;
use App\Entity\LongDrive\LongDrivePartner;
use CarlBundle\Entity\Basebuy\Equipment;
use CarlBundle\Entity\Basebuy\Modification;
use CarlBundle\Entity\Model\Model;
use CarlBundle\Exception\InvalidValueException;
use Doctrine\ORM\EntityManagerInterface;

class LongDriveStockFactory
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function fillStockModel(LongDriveModel $stockModel, CreateLongDriveStockModelRequest $request): LongDriveModel
    {
        /** @var Model $model */
        $model = $this->entityManager->getRepository(Model::class)->find($request->modelId);
        if (!$model) {
            throw new InvalidValueException('Модель не найдена');
        }

        $partner = $this->entityManager->getRepository(LongDrivePartner::class)->find($request->partnerId);
        if (!$partner) {
            throw new InvalidValueException('Партнер не найден');
        }

        $modification = null;
        if ($request->modificationId) {
            /** @var Modification $modification */
            $modification = $this->entityManager->getRepository(Modification::class)->find($request->modificationId);
            if (!$modification) {
                throw new InvalidValueException('Модификация не найдена');
            }
        }

        $equipment = null;
        if ($request->equipmentId) {
            /** @var Equipment $equipment */
            $equipment = $this->entityManager->getRepository(Equipment::class)->find($request->equipmentId);
            if (!$equipment) {
                throw new InvalidValueException('Комплектация не найдена');
            }
        }

        foreach($request->prices as $day => $price) {
            $request->prices[$day] = (int) $price;
        }

        $stockModel->setModel($model)
            ->setPartner($partner)
            ->setDescription($request->description)
            ->setPrices($request->prices)
            ->setPrice(min($request->prices))
            ->setModification($modification)
            ->setEquipment($equipment);

        return $stockModel;
    }
}
