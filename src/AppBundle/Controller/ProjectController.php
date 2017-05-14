<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    /**
     * @Route("/projects", methods={"GET"})
     *
     * @return Response
     */
    public function getCAction()
    {
        return $this->handleResponse($this->get('app.manager.project')->getProjects());
    }
}
