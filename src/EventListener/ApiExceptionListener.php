<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ApiExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();
        if (strpos($request->getPathInfo(), '/api') !== 0) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        // Personnaliser certains messages d'erreur courants
        if ($exception instanceof NotFoundHttpException) {
            $message = 'Ressource non trouvée';
        } elseif ($exception instanceof MethodNotAllowedHttpException) {
            $message = 'Méthode HTTP non autorisée';
        } else {
            $message = $exception->getMessage() ?: 'Une erreur est survenue';
        }

        $data = [
            'error' => $message,
            'code' => $statusCode
        ];

        $event->setResponse(new JsonResponse($data, $statusCode));
    }
}
