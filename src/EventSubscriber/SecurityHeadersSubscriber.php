<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ajoute des en-têtes de sécurité (défense en profondeur) sur toutes les réponses.
 * Pas de Content-Security-Policy ici : elle nécessiterait d'autoriser
 * explicitement js.stripe.com (script-src) et les iframes Stripe (frame-src).
 */
final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('X-Frame-Options', 'SAMEORIGIN');          // anti-clickjacking
        $headers->set('X-Content-Type-Options', 'nosniff');      // anti MIME-sniffing
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
