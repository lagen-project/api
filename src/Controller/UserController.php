<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * @Route("/login", methods={"POST"})
     */
    public function login(): JsonResponse
    {
        return new JsonResponse();
    }

    /**
     * @Route("/me", methods={"GET"})
     */
    public function me(): Response
    {
        return $this->handleResponse($this->getUser());
    }
}
