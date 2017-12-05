<?php

namespace App\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    /**
     * @param string $className
     * @param array $where
     * @param array $orderBy
     *
     * @return Response
     */
    protected function handleBasicCollection(string $className, array $where = [], array $orderBy = [])
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
     * @param object|array $object
     * @param int $statusCode
     * @param array $groups
     *
     * @return Response
     */
    protected function handleResponse($object, int $statusCode = Response::HTTP_OK, array $groups = [])
    {
        $context = $groups ? SerializationContext::create()->setGroups($groups) : null;
        $serialized = $this->get('jms_serializer')->serialize($object, 'json', $context);

        return $this->handleSerializedResponse($serialized, $statusCode);
    }

    /**
     * @param string $serialized
     * @param int $statusCode
     *
     * @return Response
     */
    protected function handleSerializedResponse(string $serialized, int $statusCode = Response::HTTP_OK)
    {
        return new Response($serialized, $statusCode, ['Content-type' => 'application/json']);
    }
}
