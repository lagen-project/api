<?php

namespace App\Controller;

use App\Exception\FeatureNotParsableException;
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
     */
    public function getCollection(): Response
    {
        return $this->handleResponse([]);
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}", methods={"GET"})
     */
    public function getSingle(string $projectSlug, string $featureSlug): Response
    {
        return $this->handleResponse(
            $this->get(FeatureManager::class)->getFeature($projectSlug, $featureSlug)
        );
    }

    /**
     * @Route("/projects/{projectSlug}/features", methods={"POST"})
     */
    public function post(string $projectSlug, Request $request): Response
    {
        $requestContent = json_decode($request->getContent(), true);
        $this->get(FeatureManager::class)->createFeature($projectSlug, $requestContent['name']);

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}", methods={"PUT"})
     */
    public function put(string $projectSlug, string $featureSlug, Request $request): Response
    {
        $feature = $this
            ->get('jms_serializer')
            ->deserialize($request->getContent(), Feature::class, 'json');

        $this->get(FeatureManager::class)->editFeature($projectSlug, $featureSlug, $feature);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}", methods={"DELETE"})
     */
    public function delete(string $projectSlug, string $featureSlug): Response
    {
        $this->get(FeatureManager::class)->deleteFeature($projectSlug, $featureSlug);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}/export", methods={"GET"})
     */
    public function export(string $projectSlug, string $featureSlug): Response
    {
        return new Response(
            $this->get(FeatureManager::class)->exportFeature($projectSlug, $featureSlug)
        );
    }

    /**
     * @Route("/projects/{projectSlug}/features/import", methods={"POST"})
     */
    public function import(string $projectSlug, Request $request): JsonResponse
    {
        try {
            $this
                ->get(FeatureManager::class)
                ->importFeature(
                    $projectSlug,
                    $request->get('feature_dir'),
                    $request->get('feature_filename'),
                    $request->get('content')
                );
        } catch (FeatureNotParsableException $e) {
            return new JsonResponse(null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}/run", methods={"GET"})
     */
    public function run(string $projectSlug, string $featureSlug): Response
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
     * @Route("/projects/{projectSlug}/features/{featureSlug}/last-result", methods={"GET"})
     */
    public function lastResult(string $projectSlug, string $featureSlug): Response
    {
        return $this->handleResponse($this->get(FeatureManager::class)->getResult($projectSlug, $featureSlug));
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}/metadata", methods={"GET"})
     */
    public function getMetadata(string $projectSlug, string $featureSlug): Response
    {
        $metadata = $this
            ->get(FeatureManager::class)
            ->getFeatureMetadata($projectSlug, $featureSlug);

        return $this->handleResponse($metadata);
    }

    /**
     * @Route("/projects/{projectSlug}/features/{featureSlug}/metadata", methods={"POST"})
     */
    public function postMetadata(string $projectSlug, string $featureSlug, Request $request): Response
    {
        $this
            ->get(FeatureManager::class)
            ->setFeatureMetadata($projectSlug, $featureSlug, $request->get('metadata'));

        return new JsonResponse();
    }
}
