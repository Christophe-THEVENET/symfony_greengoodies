<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    /**
     * Bascule la langue de l'interface (FR/EN) et la mémorise en session.
     * Redirige vers la page d'origine (referer) si elle est interne, sinon l'accueil.
     */
    #[Route('/locale/{_locale}', name: 'app_locale', requirements: ['_locale' => 'fr|en'])]
    public function switch(string $_locale, Request $request): Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set('_locale', $_locale);
        }

        // Anti open-redirect : on ne suit le referer que s'il pointe vers le même hôte
        $referer = $request->headers->get('referer');
        if ($referer) {
            $refHost = parse_url($referer, PHP_URL_HOST);
            if ($refHost === $request->getHost()) {
                return $this->redirect($referer);
            }
        }

        return $this->redirectToRoute('app_home');
    }
}
