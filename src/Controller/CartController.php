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

    // ************** cart page **************
    #[Route('/', name: 'app_cart', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $this->cartService->getCart(),
        ]);
    }

    // ************** add product to cart (ajax front-end) **************
    #[Route('/add/{productId}', name: 'api_cart_add', methods: ['POST'])]
    public function add(int $productId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $quantity = $data['quantity'] ?? 1;

            $this->cartService->addProduct($productId, $quantity);

            return $this->json([
                'success'    => true,
                'message'    => 'Produit ajouté au panier',
                'cart_count' => $this->cartService->getCart()->getItemCount(),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // ************** update product quantity in cart (ajax page panier) **************
    #[Route('/update/{productId}', name: 'api_cart_update', methods: ['PUT'])]
    public function update(int $productId, Request $request): JsonResponse
    {
        try {
            $data     = json_decode($request->getContent(), true);
            $quantity = $data['quantity'] ?? 1;
            $updatedQuantity = $this->cartService->updateQuantity($productId, $quantity);
            /* $updatedTotalPrice = $this->cartService->getCart()->getTotalAmount(); */
            $itemTotalPrice = $this->cartService->getCart()->getItemTotalPrice($productId); // Récupérer le total de l'item

            return $this->json([
                'success' => true,
                'message' => 'Quantité mise à jour',
                'cart'    => [
                    'total' => $this->cartService->getCart()->getTotalAmount(),
                    'count' => $this->cartService->getCart()->getItemCount(),
                    'updatedItem' => [
                        'product' => [
                            'id' => $productId,
                        ],
                        'quantity' => $updatedQuantity,
                        'total_price' => $itemTotalPrice, // <= total de l'item, pas du panier
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // remove product from cart (bouton supprimer sur chaque ligne page panier)
    #[Route('/remove/{productId}', name: 'api_cart_remove', methods: ['DELETE'])]
    public function remove(int $productId): JsonResponse
    {
        try {
            $this->cartService->removeProduct($productId);

            return $this->json([
                'success' => true,
                'message' => 'Produit retiré du panier',
                'cart' => [
                    'total' => $this->cartService->getCart()->getTotalAmount(),
                    'count' => $this->cartService->getCart()->getItemCount(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    // ************** clear cart (bouton vider panier page panier) **************
    #[Route('/clear', name: 'api_cart_clear', methods: ['POST'])]
    public function clear(): JsonResponse
    {
        $this->cartService->clearCart();

        return $this->json([
            'success' => true,
            'message' => 'Panier vidé',
        ]);
    }
    // ************** validate cart and create order (bouton valider panier page panier) **************
    #[Route('/validate', name: 'app_cart_validate', methods: ['POST'])]
    public function validate(): Response
    {
        if (! $this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $order = $this->cartService->validateCart($this->getUser());

            /*  $this->addFlash('success', 'Commande validée avec succès !'); */
            return $this->redirectToRoute('app_account');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_cart');
        }
    }
}
