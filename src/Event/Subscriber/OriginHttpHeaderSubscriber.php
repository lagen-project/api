<?php

namespace App\Event\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OriginHttpHeaderSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $allowedOrigins;

    public function __construct(string $allowedOrigins)
    {
        $this->allowedOrigins = $allowedOrigins;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequest()->getMethod() === Request::METHOD_OPTIONS) {
            $response = new Response();
            $response->headers->add([
                'Access-Control-Allow-Origin' => $this->allowedOrigins,
                'Access-Control-Allow-Methods' => implode(',', [
                    Request::METHOD_GET,
                    Request::METHOD_POST,
                    Request::METHOD_PUT,
                    Request::METHOD_DELETE
                ]),
                'Access-Control-Allow-Headers' => implode(',', [
                    'content-type',
                    'Authorization'
                ])
            ]);
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->add([
            'Access-Control-Allow-Origin' => $this->allowedOrigins
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }
}
