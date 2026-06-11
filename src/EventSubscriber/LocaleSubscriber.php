<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Définit la locale de la requête à partir de la session.
 * Permet le bilingue FR/EN sans préfixe d'URL : le choix de langue
 * est mémorisé en session et réappliqué à chaque requête.
 */
final class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%kernel.default_locale%')]
        private readonly string $defaultLocale,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité 20 : avant le LocaleListener par défaut de Symfony
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        // La locale stockée en session prime, sinon la locale par défaut
        $locale = $request->getSession()->get('_locale', $this->defaultLocale);
        $request->setLocale($locale);
    }
}
