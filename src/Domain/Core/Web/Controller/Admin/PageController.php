<?php

namespace App\Domain\Core\Web\Controller\Admin;

use App\Domain\Core\Web\Controller\Admin\Request\CreatePageRequest;
use App\Domain\Core\Web\Repository\PageRepository;
use App\Entity\Web\Page;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Response\Common\BooleanResponse;
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
     *     operationId="admin/web/pages/list"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Вернёт массив страниц",
     *     @OA\JsonContent(
     *          @OA\Property(type="array", property="items", @OA\Items(ref=@Model(type=Page::class)))
     *     )
     * )
     *
     * @OA\Tag(name="Web\Admin\Pages")
     *
     * @return array
     */
    public function list(): array
    {
        return ['items' => $this->pageRepository->findAll()];
    }

    /**
     * Получить конкретную страницу сайта
     *
     * @OA\Get(
     *     operationId="admin/web/pages/show",
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
     * @OA\Tag(name="Web\Admin\Pages")
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

    /**
     * Создать или обновить существующую страницу сайта
     *
     * @OA\Post(
     *     operationId="admin/web/pages/createOrUpdate",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  ref=@Model(type=CreatePageRequest::class)
     *              )
     *          )
     *     ),
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
     *     description="Вернёт итоговую страницу",
     *     @Model(type=Page::class)
     * )
     *
     * @OA\Response(
     *     response=461,
     *     description="Ошибка при создании или обновлении страницы"
     * )
     *
     * @OA\Tag(name="Web\Admin\Pages")
     *
     * @param CreatePageRequest $pageRequest
     * @param Request           $request
     *
     * @return Page
     * @throws InvalidValueException
     */
    public function createOrUpdate(CreatePageRequest $pageRequest, Request $request): Page
    {
        $url = $request->get('url');
        $page = $this->pageRepository->findOneBy(['url' => $url]);
        if (!$page) {
            $page = new Page();
            $page->setUrl($url);
        }

        $page->setName($pageRequest->name)
            ->setTitle($pageRequest->title)
            ->setDescription($pageRequest->description)
            ->setEmbed($pageRequest->embed);

        try {
            $this->pageRepository->persist($page);
            $this->pageRepository->flush();
        } catch (\Exception $ex) {
            throw new InvalidValueException('Не удалось создать или обновить сущность: ' . $ex->getMessage());
        }

        return $page;
    }

    /**
     * Создать или обновить существующую страницу сайта
     *
     * @OA\Get(
     *     operationId="admin/web/pages/delete",
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
     *     description="Вернёт результат удаления",
     *     @Model(type=BooleanResponse::class)
     * )
     *
     * @OA\Response(response=404, description="Запрошенная страница не найдена")
     * @OA\Response(response=461, description="Ошибка при создании или обновлении страницы")
     *
     * @OA\Tag(name="Web\Admin\Pages")
     *
     * @param Request $request
     *
     * @return BooleanResponse
     * @throws InvalidValueException
     */
    public function delete(Request $request): BooleanResponse
    {
        $url = $request->get('url');
        $page = $this->pageRepository->findOneBy(['url' => $url]);
        if (!$page) {
            throw new NotFoundHttpException("Страница {$url} не найдена");
        }

        try {
            $this->pageRepository->remove($page);
            $this->pageRepository->flush();
        } catch (\Exception $ex) {
            throw new InvalidValueException('Не удалось удалить сущность: ' . $ex->getMessage());
        }

        return new BooleanResponse(true);
    }
}