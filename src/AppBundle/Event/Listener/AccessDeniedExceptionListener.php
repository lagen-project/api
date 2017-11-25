<?php

namespace AppBundle\Event\Listener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccessDeniedExceptionListener
{
    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse([
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED));
        }
    }
}
