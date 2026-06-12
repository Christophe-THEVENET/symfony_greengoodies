<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    #[Route('/locale/{_locale}', name: 'app_locale', requirements: ['_locale' => 'fr|en'])]
    public function switch(string $_locale, Request $request): Response
    {
        // Stocke en session ET en cookie (fallback)
        if ($request->hasSession()) {
            $request->getSession()->set('_locale', $_locale);
        }

        $referer = $request->headers->get('referer');
        if ($referer) {
            $refHost = parse_url($referer, PHP_URL_HOST);
            if ($refHost === $request->getHost()) {
                $response = $this->redirect($referer);
                $response->headers->setCookie(Cookie::create('gg_lang', $_locale, strtotime('+30 days'), '/', null, false, false));
                return $response;
            }
        }

        $response = $this->redirectToRoute('app_home');
        $response->headers->setCookie(Cookie::create('gg_lang', $_locale, strtotime('+30 days'), '/', null, false, false));
        return $response;
    }
}
