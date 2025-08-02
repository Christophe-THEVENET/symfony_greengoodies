<?php
// src/Controller/AccountController.php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

#[Route('/mon-compte')]
class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TokenStorageInterface $tokenStorage,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    #[Route('/', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        $orders = $orderRepository->findLastFiveValidOrdersByUser($user);

        return $this->render('security/account.html.twig', [
            'user' => $user,
            'orders' => $orders
        ]);
    }

    #[Route('/supprimer', name: 'app_account_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        TokenStorageInterface $tokenStorage,
        Request $request
    ): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete_account', $submittedToken))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();

        //logout l'utilisateur avant de supprimer son compte
        $tokenStorage->setToken(null);

        // SOLUTION : Invalider la session utilisateur après suppression
        $request->getSession()->invalidate();

        $this->em->remove($user);
        $this->em->flush();

        // add message on session (pour Stimulus)
        $request->getSession()->set('toast', 'Votre compte a bien été supprimé.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/access-api', name: 'api_account_toggle_api', methods: ['POST'])]
    public function toggleApiAccess(Request $request): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('access_api', $submittedToken))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['message' => 'Token CSRF invalide.'], 403);
            }
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        /** @var User $user */
        $user = $this->getUser();

        $enabled = !$user->isApiAccessEnabled();
        $user->setApiAccessEnabled($enabled);
        $this->em->flush();

        $message = $enabled ? 'Accès API activé avec succès.' : 'Accès API désactivé avec succès.';

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'enabled' => $enabled,
                'message' => $message
            ]);
        }
        $request->getSession()->set('toast', $message);

        return $this->redirectToRoute('app_account');
    }
}
