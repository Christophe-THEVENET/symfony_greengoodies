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

class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account')]
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

    #[Route('/mon-compte/supprimer', name: 'app_account_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Request $request,
         EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager,
        TokenStorageInterface $tokenStorage
          ): Response
    {

        $submittedToken = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_account', $submittedToken))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();


        //logout l'utilisateur avant de supprimer son compte
        $tokenStorage->setToken(null);

        // SOLUTION : Invalider la session utilisateur après suppression
        $request->getSession()->invalidate();


        $em->remove($user);
        $em->flush();

       


        // add message on session (pour Stimulus)
        $request->getSession()->set('toast', 'Votre compte a bien été supprimé.');

        return $this->redirectToRoute('app_home');
    }
}
