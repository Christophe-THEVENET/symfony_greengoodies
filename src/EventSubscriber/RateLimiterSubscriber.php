<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Applique un rate limiting sur les endpoints sensibles.
 * S'exécute avant le firewall pour bloquer les attaques brute-force.
 */
final class RateLimiterSubscriber implements EventSubscriberInterface
{
    private const PROTECTED_ROUTES = [
        'app_login' => 'login',
        'api_login_check' => 'api_login',
        'app_register' => 'register',
    ];

    public function __construct(
        private readonly RateLimiterFactory $loginLimiter,
        private readonly RateLimiterFactory $apiLoginLimiter,
        private readonly RateLimiterFactory $registerLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité 10 : avant le firewall (8), pour bloquer avant l'auth
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        // Ne protéger que les méthodes mutatives
        if (!\in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if (!isset(self::PROTECTED_ROUTES[$routeName])) {
            return;
        }

        $limiterName = self::PROTECTED_ROUTES[$routeName];
        $limiter = match ($limiterName) {
            'login' => $this->loginLimiter,
            'api_login' => $this->apiLoginLimiter,
            'register' => $this->registerLimiter,
        };

        // Limiter par IP (anonyme ou non)
        $clientIp = $request->getClientIp() ?? '127.0.0.1';
        $limit = $limiter->create($clientIp)->consume();

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();

            // Pour l'API, retourner du JSON
            if (str_starts_with($request->getPathInfo(), '/api')) {
                $response = new JsonResponse(
                    ['error' => 'Trop de requêtes. Réessayez plus tard.', 'code' => 429],
                    Response::HTTP_TOO_MANY_REQUESTS,
                    ['Retry-After' => $retryAfter],
                );
                $event->setResponse($response);

                return;
            }

            throw new TooManyRequestsHttpException($retryAfter, 'Trop de tentatives. Veuillez réessayer plus tard.');
        }
    }
}
