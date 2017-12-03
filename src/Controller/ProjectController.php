<?php

namespace App\Controller;

use App\Exception\ProjectNotFoundException;
use App\Manager\ProjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        return $this->handleResponse($this->get(ProjectManager::class)->getProjects());
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
            $project = $this->get(ProjectManager::class)->getProject($projectSlug);

            return $this->handleResponse($project);
        } catch (ProjectNotFoundException $e) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @Route("/projects", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $requestContent = json_decode($request->getContent(), true);
        $this->get(ProjectManager::class)->createProject($requestContent['name']);

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    /**
     * @Route("/projects/{projectSlug}", methods={"PUT"})
     *
     * @param string $projectSlug
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function putAction($projectSlug, Request $request)
    {
        $this->get(ProjectManager::class)->editProject(
            $projectSlug,
            json_decode($request->getContent(), true)
        );

        return new JsonResponse(null);
    }

    /**
     * @Route("/projects/{projectSlug}/install", methods={"GET"})
     *
     * @param string $projectSlug
     *
     * @return StreamedResponse
     */
    public function installAction($projectSlug)
    {
        return $this->get(ProjectManager::class)->installProject($projectSlug);
    }

    /**
     * @Route("/projects/{projectSlug}/git", methods={"GET"})
     *
     * @param string $projectSlug
     *
     * @return JsonResponse
     */
    public function gitAction($projectSlug)
    {
        return new JsonResponse($this->get(ProjectManager::class)->retrieveProjectGitInfo($projectSlug));
    }

    /**
     * @Route("/projects/{projectSlug}/steps", methods={"GET"})
     *
     * @param string $projectSlug
     *
     * @return JsonResponse
     */
    public function stepsAction($projectSlug)
    {
        return new JsonResponse($this->get(ProjectManager::class)->retrieveSteps($projectSlug));
    }
}
