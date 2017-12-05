<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * @Route("/login", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function login()
    {
        return new JsonResponse();
    }

    /**
     * @Route("/me", methods={"GET"})
     *
     * @return Response
     */
    public function me()
    {
        return $this->handleResponse($this->getUser());
    }
}
