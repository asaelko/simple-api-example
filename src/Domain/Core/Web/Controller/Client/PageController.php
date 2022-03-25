<?php

namespace App\Domain\Core\Web\Controller\Client;

use App\Domain\Core\Web\Repository\PageRepository;
use App\Entity\Web\Page;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends AbstractController
{
    private PageRepository $pageRepository;

    public function __construct(
        PageRepository $pageRepository
    )
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * Получить список страниц сайта
     *
     * @OA\Get(
     *     operationId="web/pages/list"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив страниц",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              type="array",
     *              property="items",
     *              @OA\Items(
     *                  ref=@Model(type=Page::class)
     *              )
     *          )
     *     )
     * )
     *
     * @OA\Tag(name="Web\Pages")
     *
     * @return array
     */
    public function list(): array
    {
        return $this->pageRepository->findAll();
    }

    /**
     * Получить конкретную страницу сайта
     *
     * @OA\Get(
     *     operationId="web/pages/show",
     *     @OA\Parameter(
     *          name="url",
     *          in="query",
     *          required=true,
     *          description="Адрес страницы сайта",
     *          @OA\Schema(type="string")
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт запрошенную страницу",
     *     @Model(type=Page::class)
     * )
     *
     * @OA\Response(response=404, description="Запрошенная страница не найдена")
     *
     * @OA\Tag(name="Web\Pages")
     *
     * @param Request $request
     *
     * @return Page
     */
    public function show(Request $request): Page
    {
        $url = $request->get('url');
        $page = $this->pageRepository->findOneBy(['url' => $url]);
        if (!$page) {
            throw new NotFoundHttpException("Страница {$url} не найдена");
        }

        return $page;
    }
}