<?php

namespace App\Controller;

use App\Exception\FeatureRunErrorException;
use App\Manager\FeatureManager;
use App\Model\Feature;
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
            $this->get(FeatureManager::class)->getFeature($projectSlug, $featureSlug)
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
        $this->get(FeatureManager::class)->createFeature($projectSlug, $requestContent['name']);

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

        $this->get(FeatureManager::class)->editFeature($projectSlug, $featureSlug, $feature);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}/export", methods={"GET"})
     *
     * @param string $projectSlug
     * @param string $featureSlug
     *
     * @return Response
     */
    public function exportAction($projectSlug, $featureSlug)
    {
        return new Response(
            $this->get(FeatureManager::class)->exportFeature($projectSlug, $featureSlug)
        );
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}/run", methods={"GET"})
     *
     * @param string $projectSlug
     * @param string $featureSlug
     *
     * @return Response
     */
    public function runAction($projectSlug, $featureSlug)
    {
        try {
            return $this->handleResponse($this->get(FeatureManager::class)->runFeature($projectSlug, $featureSlug));
        } catch (FeatureRunErrorException $e) {
            return new JsonResponse([
                'error' => 'feature_run',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}/metadata", methods={"GET"})
     *
     * @param string $projectSlug
     * @param string $featureSlug
     *
     * @return Response
     */
    public function getMetadataAction($projectSlug, $featureSlug)
    {
        $metadata = $this
            ->get(FeatureManager::class)
            ->getFeatureMetadata($projectSlug, $featureSlug);

        return $this->handleResponse($metadata);
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}/metadata", methods={"POST"})
     *
     * @param string $projectSlug
     * @param string $featureSlug
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function postMetadataAction($projectSlug, $featureSlug, Request $request)
    {
        $this
            ->get(FeatureManager::class)
            ->setFeatureMetadata($projectSlug, $featureSlug, $request->get('metadata'));

        return new JsonResponse();
    }
}
