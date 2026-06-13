<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Point d'entrée du firewall : quand un utilisateur non connecté tente
 * d'accéder à une page protégée (ex. le paiement), on dépose un message en
 * session (affiché en toast sur la page de login) avant de rediriger.
 */
final class LoginRequiredEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Message repris par base.html.twig (session PHP -> sessionStorage -> toast)
        $request->getSession()->set('error', $this->translator->trans('toast.login_required'));

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
