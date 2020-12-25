<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\AuthException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Exception\HttpException;

class AfterActionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'handleException',
        ];
    }

    public function handleException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AuthException) {
            $exception = new HttpException($exception);
        } elseif (!($exception instanceof HttpException)) {
            return;
        }

        $data = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'context' => [
                'file' => $exception->getPrevious() !== null ? $exception->getPrevious()->getFile() : $exception->getFile(),
                'line' => $exception->getPrevious() !== null ? $exception->getPrevious()->getLine() : $exception->getLine(),
            ],
        ];

        $response = new JsonResponse(['error' => $data], $exception->getStatusCode());
        if (!empty($exception->getHeaders())) {
            $response->headers->add($exception->getHeaders());
        }
        $response->headers->add([
            'access-control-expose-headers' => 'X-Debug-Token,X-Debug-Token-Link',
        ]);
        $event->setResponse($response);
    }
}