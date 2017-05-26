<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureController extends Controller
{
    /**
     * @Route("/projects/{projectId}/features", methods={"GET"})
     *
     * @return Response
     */
    public function getCAction()
    {
        return $this->handleResponse([]);
    }

    /**
     * @Route("/projects/{projectId}/features/{featureId}", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getAction(Request $request)
    {
        return $this->handleResponse([]);
    }

    /**
     * @Route("/projects/{projectSlug}/features", methods={"POST"})
     *
     * @param string $projectSlug
     * @param Request $request
     *
     * @return Response
     */
    public function postAction($projectSlug, Request $request)
    {
        $requestContent = json_decode($request->getContent(), true);
        $this->get('app.manager.feature')->createFeature($projectSlug, $requestContent['name']);

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    /**
     * @Route("/projects/{projectId}/features/{id}", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function putAction(Request $request)
    {
        return $this->handleResponse([]);
    }
}
