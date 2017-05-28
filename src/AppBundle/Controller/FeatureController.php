<?php

namespace AppBundle\Controller;

use AppBundle\Model\Feature;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureController extends Controller
{
    /**
     * @Route("/projects/{projectSlug}/features", methods={"GET"})
     *
     * @return Response
     */
    public function getCAction()
    {
        return $this->handleResponse([]);
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}", methods={"GET"})
     *
     * @param string $projectSlug
     * @param string $featureSlug
     *
     * @return Response
     */
    public function getAction($projectSlug, $featureSlug)
    {
        return $this->handleResponse(
            $this->get('app.manager.feature')->getFeature($projectSlug, $featureSlug)
        );
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
     * @Route("/projects/{projectSlug}/features/{featureSlug}", methods={"PUT"})
     *
     * @param string $projectSlug
     * @param string $featureSlug
     * @param Request $request
     *
     * @return Response
     */
    public function putAction($projectSlug, $featureSlug, Request $request)
    {
        $feature = $this
            ->get('jms_serializer')
            ->deserialize($request->getContent(), Feature::class, 'json');

        $this->get('app.manager.feature')->editFeature($projectSlug, $featureSlug, $feature);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
