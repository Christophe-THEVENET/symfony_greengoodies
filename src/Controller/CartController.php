<?php
// src/Controller/CartController.php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    public function __construct(private CartService $cartService) {}
    // cart page
    #[Route('/', name: 'app_cart', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $this->cartService->getCart()
        ]);
    }
    // add product to cart
    #[Route('/add/{productId}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $productId, Request $request): JsonResponse
    {
        try {
            $quantity = $request->request->getInt('quantity', 1);
            $this->cartService->addProduct($productId, $quantity);

            return $this->json([
                'success' => true,
                'message' => 'Produit ajouté au panier',
                'cart_count' => $this->cartService->getCart()->getItemCount()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    // update product quantity in cart
    #[Route('/update/{productId}', name: 'cart_update', methods: ['POST'])]
    public function update(int $productId, Request $request): JsonResponse
    {
        try {
            $quantity = $request->request->getInt('quantity');
            $this->cartService->updateQuantity($productId, $quantity);

            return $this->json([
                'success' => true,
                'message' => 'Quantité mise à jour',
                'cart' => [
                    'total' => $this->cartService->getCart()->getTotalAmount(),
                    'count' => $this->cartService->getCart()->getItemCount()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    // remove product from cart
    #[Route('/remove/{productId}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $productId): JsonResponse
    {
        try {
            $this->cartService->removeProduct($productId);

            return $this->json([
                'success' => true,
                'message' => 'Produit retiré du panier'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    // clear cart
    #[Route('/clear', name: 'cart_clear', methods: ['POST'])]
    public function clear(): JsonResponse
    {
        $this->cartService->clearCart();

        return $this->json([
            'success' => true,
            'message' => 'Panier vidé'
        ]);
    }
    // validate cart and create order
    #[Route('/validate', name: 'cart_validate', methods: ['POST'])]
    public function validate(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $order = $this->cartService->validateCart($this->getUser());

            $this->addFlash('success', 'Commande validée avec succès !');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('cart_show');
        }
    }
}
