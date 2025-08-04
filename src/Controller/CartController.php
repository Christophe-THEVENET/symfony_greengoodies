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
    // Messages constants
    private const MSG_PRODUCT_ADDED = 'Produit ajouté au panier';
    private const MSG_QUANTITY_UPDATED = 'Quantité mise à jour';
    private const MSG_PRODUCT_REMOVED = 'Produit retiré du panier';
    private const MSG_CART_CLEARED = 'Panier vidé';
    private const MSG_ORDER_VALIDATED = 'Commande validée avec succès !';
    private const MSG_LOGIN_REQUIRED = 'Vous devez être connecté pour valider la commande.';
    private const MSG_INVALID_DATA = 'Données invalides';

    public function __construct(private CartService $cartService) {}

    // ************** cart page **************
    #[Route('/', name: 'app_cart', methods: ['GET'])]
    public function show(): Response
    {
        $cart = $this->cartService->getCart();
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'cart_count' => $cart->getItemCount(),
        ]);
    }

    // ************** add product to cart (ajax front-end) **************
    #[Route('/add/{productId}', name: 'api_cart_add', methods: ['POST'])]
    public function add(int $productId, Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonData($request);
            $quantity = $data['quantity'] ?? 1;

            $this->cartService->addProduct($productId, $quantity);

            return $this->json([
                'success'    => true,
                'message'    => self::MSG_PRODUCT_ADDED,
                'cart_count' => $this->cartService->getCart()->getItemCount(),
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse($e);
        }
    }

    // ************** update product quantity in cart (ajax page panier) **************
    #[Route('/update/{productId}', name: 'api_cart_update', methods: ['PUT'])]
    public function update(int $productId, Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonData($request);
            $quantity = $data['quantity'] ?? 1;
            $updatedQuantity = $this->cartService->updateQuantity($productId, $quantity);
            $cart = $this->cartService->getCart();
            $itemTotalPrice = $cart->getItemTotalPrice($productId);

            return $this->json([
                'success' => true,
                'message' => self::MSG_QUANTITY_UPDATED,
                'cart'    => [
                    'total' => $cart->getTotalAmount(),
                    'count' => $cart->getItemCount(),
                    'updatedItem' => [
                        'product' => [
                            'id' => $productId,
                        ],
                        'quantity' => $updatedQuantity,
                        'total_price' => $itemTotalPrice,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse($e);
        }
    }

    // ************** remove product from cart (bouton supprimer sur chaque ligne page panier) **************
    #[Route('/remove/{productId}', name: 'api_cart_remove', methods: ['DELETE'])]
    public function remove(int $productId): JsonResponse
    {
        try {
            $this->cartService->removeProduct($productId);
            $cart = $this->cartService->getCart();

            return $this->json([
                'success' => true,
                'message' => self::MSG_PRODUCT_REMOVED,
                'cart' => [
                    'total' => $cart->getTotalAmount(),
                    'count' => $cart->getItemCount(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse($e);
        }
    }

    // ************** clear cart (bouton vider panier page panier) **************
    #[Route('/clear', name: 'api_cart_clear', methods: ['POST'])]
    public function clear(): JsonResponse
    {
        $this->cartService->clearCart();

        return $this->json([
            'success' => true,
            'message' => self::MSG_CART_CLEARED,
            'redirectUrl' => $this->generateUrl('app_cart'),
        ]);
    }

    // ************** validate cart and create order (bouton valider panier page panier) **************
    #[Route('/validate', name: 'api_cart_validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json([
                'success' => false,
                'message' => self::MSG_LOGIN_REQUIRED,
                'redirectUrl' => $this->generateUrl('app_login'),
            ], 401);
        }

        try {
            $order = $this->cartService->validateCart($this->getUser());

            return $this->json([
                'success' => true,
                'message' => self::MSG_ORDER_VALIDATED,
                'redirectUrl' => $this->generateUrl('app_home'),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'redirectUrl' => $this->generateUrl('app_cart'),
            ], 400);
        }
    }

    // ************** Utility methods **************

    /**
     * Parse and validate JSON data from request
     */
    private function getJsonData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException(self::MSG_INVALID_DATA);
        }
        return $data;
    }

    /**
     * Create standardized error response
     */
    private function createErrorResponse(\Exception $e, int $status = 400): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $status);
    }
}
