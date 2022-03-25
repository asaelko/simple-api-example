<?php

namespace App\Domain\WebSite\News\Service;

use App\Entity\News;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\Media\Media;
use CarlBundle\Entity\Model\Model;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function fillNews(News $news, Media $photo, $request): News
    {
        $news->setTitle($request->title);
        $news->setShortDescription($request->shortDescription);
        $news->setDescription($request->description);
        $news->setIsActive($request->isActive);
        $news->setPhoto($photo);
        $news->setLink($request->link);
        $news->setActionText($request->actionText);

        if ($request->brandId) {
            /** @var Brand $brand */
            $brand = $this->em->getRepository(Brand::class)->find($request->brandId);
            if (!$brand) {
                throw new NotFoundHttpException('Бренд не найден');
            }
            $news->setBrand($brand);
        }

        if ($request->modelId) {
            /** @var Model $model */
            $model = $this->em->getRepository(Model::class)->find($request->modelId);
            if (!$model) {
                throw new NotFoundHttpException('Модель не найдена');
            }
            $news->setModel($model);
        }

        return $news;
    }
}