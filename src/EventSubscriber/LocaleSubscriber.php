<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 20]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Priorité : route > session > cookie > défaut
        if ($request->attributes->has('_locale')) {
            $locale = $request->attributes->get('_locale');
        } elseif ($request->hasSession() && $request->getSession()->get('_locale')) {
            $locale = $request->getSession()->get('_locale');
        } elseif ($request->cookies->has('gg_lang')) {
            $locale = $request->cookies->get('gg_lang');
        } else {
            return;
        }

        $request->setLocale($locale);
    }
}
