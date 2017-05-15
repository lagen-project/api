<?php

namespace AppBundle\Controller;

use AppBundle\Exception\ProjectNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**
     * @Route("/projects/{projectSlug}", methods={"GET"})
     *
     * @param string $projectSlug
     *
     * @return Response
     */
    public function getAction($projectSlug)
    {
        try {
            $project = $this->get('app.manager.project')->getProject($projectSlug);

            return $this->handleResponse($project);
        } catch (ProjectNotFoundException $e) {
            throw new NotFoundHttpException();
        }
    }
}
