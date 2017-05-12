<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureController extends Controller
{
    /**
     * @Route("/project/{projectId}/features", methods={"GET"})
     *
     * @return Response
     */
    public function getCAction()
    {
        return $this->handleResponse([]);
    }

    /**
     * @Route("/project/{projectId}/features/{featureId}", methods={"GET"})
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
     * @Route("/project/{projectId}/features", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        return $this->handleResponse([], Response::HTTP_CREATED);
    }

    /**
     * @Route("/project/{projectId}/features/{id}", methods={"PUT"})
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
