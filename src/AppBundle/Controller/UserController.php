<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * @Route("/me", methods={"GET"})
     *
     * @return Response
     */
    public function meAction()
    {
        return $this->handleResponse($this->getUser());
    }
}
