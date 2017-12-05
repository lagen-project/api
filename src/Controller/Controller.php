<?php

namespace App\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    protected function handleBasicCollection(string $className, array $where = [], array $orderBy = []): Response
    {
        $collection = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository($className)
            ->findBy($where, $orderBy);

        $serialized = $this->get('jms_serializer')->serialize($collection, 'json');

        return $this->handleSerializedResponse($serialized);
    }

    /**
     * @param array|object $object
     */
    protected function handleResponse($object, int $statusCode = Response::HTTP_OK, array $groups = []): Response
    {
        $context = $groups ? SerializationContext::create()->setGroups($groups) : null;
        $serialized = $this->get('jms_serializer')->serialize($object, 'json', $context);

        return $this->handleSerializedResponse($serialized, $statusCode);
    }

    protected function handleSerializedResponse(string $serialized, int $statusCode = Response::HTTP_OK): Response
    {
        return new Response($serialized, $statusCode, ['Content-type' => 'application/json']);
    }
}
