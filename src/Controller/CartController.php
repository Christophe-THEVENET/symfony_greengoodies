<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
final class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart')]
    public function index(): Response
    {
        return $this->render('cart/index.html.twig', [
            'cartItems' => [], // Récupérer les items du panier
            'total' => 0,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'add', methods: ['POST'])]
    public function add(int $id): Response
    {
        // Logique pour ajouter un produit au panier
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/supprimer/{id}', name: 'remove', methods: ['POST'])]
    public function remove(int $id): Response
    {
        // Logique pour supprimer un produit du panier
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/vider', name: 'clear', methods: ['POST'])]
    public function clear(): Response
    {
        // Logique pour vider le panier
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/commander', name: 'checkout')]
    public function checkout(): Response
    {
        // Logique pour la commande
        return $this->render('cart/checkout.html.twig');
    }
}
