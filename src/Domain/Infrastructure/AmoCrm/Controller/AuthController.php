<?php

namespace App\Domain\Infrastructure\AmoCrm\Controller;

use App\Domain\Infrastructure\AmoCrm\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends AbstractController
{
    public function index(TokenService $tokenHelper): Response
    {
        if (!isset($_GET['code'])) {
            $tokenHelper->authorization();
        }

        $tokenHelper->getTokenByCode($_GET['code']);

        return new Response('Success');
    }
}