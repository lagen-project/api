<?php

namespace App\Controller;

use App\Exception\ProjectNotFoundException;
use App\Manager\ProjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectController extends Controller
{
    /**
     * @Route("/projects", methods={"GET"})
     */
    public function getCollection(): Response
    {
        return $this->handleResponse($this->get(ProjectManager::class)->getProjects());
    }

    /**
     * @Route("/projects/{projectSlug}", methods={"GET"})
     */
    public function getSingle(string $projectSlug): Response
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
     */
    public function post(Request $request): Response
    {
        $requestContent = json_decode($request->getContent(), true);
        $this->get(ProjectManager::class)->createProject($requestContent['name']);

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    /**
     * @Route("/projects/{projectSlug}", methods={"PUT"})
     */
    public function put(string $projectSlug, Request $request): JsonResponse
    {
        $this->get(ProjectManager::class)->editProject(
            $projectSlug,
            json_decode($request->getContent(), true)
        );

        return new JsonResponse(null);
    }

    /**
     * @Route("/projects/{projectSlug}", methods={"DELETE"})
     */
    public function delete(string $projectSlug): JsonResponse
    {
        $this->get(ProjectManager::class)->deleteProject($projectSlug);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/projects/{projectSlug}/install", methods={"GET"})
     */
    public function install(string $projectSlug): JsonResponse
    {
        $this->get(ProjectManager::class)->installProject($projectSlug);

        return new JsonResponse();
    }

    /**
     * @Route("/projects/{projectSlug}/git", methods={"GET"})
     */
    public function git(string $projectSlug): JsonResponse
    {
        return new JsonResponse($this->get(ProjectManager::class)->retrieveProjectGitInfo($projectSlug));
    }

    /**
     * @Route("/projects/{projectSlug}/steps", methods={"GET"})
     */
    public function steps(string $projectSlug): JsonResponse
    {
        return new JsonResponse($this->get(ProjectManager::class)->retrieveSteps($projectSlug));
    }

    /**
     * @Route("/projects/{projectSlug}/install-status", methods={"GET"})
     */
    public function installStatus(string $projectSlug): JsonResponse
    {
        return new JsonResponse($this->get(ProjectManager::class)->getProjectInstallStatus($projectSlug));
    }
}
