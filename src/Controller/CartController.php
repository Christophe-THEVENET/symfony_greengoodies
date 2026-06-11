<?php
// src/Controller/CartController.php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/panier')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private TranslatorInterface $translator,
    ) {}

    // ************** cart page **************
    #[Route('', name: 'app_cart', methods: ['GET'])]
    public function show(): Response
    {
        $cart = $this->cartService->getCart();
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'cart_count' => $cart->getItemCount(),
        ]);
    }

    // ************** add product to cart (ajax front-end) **************
    #[Route('/ajouter/{productId}', name: 'api_cart_add', methods: ['POST'])]
    public function add(int $productId, Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonData($request);
            $quantity = $data['quantity'] ?? 1;

            $this->cartService->addProduct($productId, $quantity);

            return $this->json([
                'success'    => true,
                'message'    => $this->translator->trans('toast.product_added'),
                'cart_count' => $this->cartService->getCart()->getItemCount(),
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse($e);
        }
    }

    // ************** update product quantity in cart (ajax page panier) **************
    #[Route('/modifier/{productId}', name: 'api_cart_update', methods: ['PUT'])]
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
                'message' => $this->translator->trans('toast.quantity_updated'),
                'cart'    => [
                    'total' => $cart->getTotalAmount(),
                    'count' => $cart->getItemCount(),
                    'summary' => $cart->getSummary(),
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
    #[Route('/retirer/{productId}', name: 'api_cart_remove', methods: ['DELETE'])]
    public function remove(int $productId): JsonResponse
    {
        try {
            $this->cartService->removeProduct($productId);
            $cart = $this->cartService->getCart();

            return $this->json([
                'success' => true,
                'message' => $this->translator->trans('toast.product_removed'),
                'cart' => [
                    'total' => $cart->getTotalAmount(),
                    'count' => $cart->getItemCount(),
                    'summary' => $cart->getSummary(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse($e);
        }
    }

    // ************** clear cart (bouton vider panier page panier) **************
    #[Route('/vider', name: 'api_cart_clear', methods: ['POST'])]
    public function clear(): JsonResponse
    {
        $this->cartService->clearCart();

        return $this->json([
            'success' => true,
            'message' => $this->translator->trans('toast.cart_cleared'),
            'redirectUrl' => $this->generateUrl('app_cart'),
        ]);
    }

    // ************** validate cart and create order (bouton valider panier page panier) **************
    #[Route('/valider', name: 'api_cart_validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        // 1. Vérification de l'authentification
        if (!$this->getUser()) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('toast.login_required'),
                'redirectUrl' => $this->generateUrl('app_login'),
            ], 401);
        }

        // 2. Vérification du token CSRF
        if (!$this->isCsrfTokenValid('app_order_validate', $request->request->get('_token'))) {
            return $this->json([
                'success' => false,
                'message' => 'Token CSRF invalide.',
            ], 403);
        }

        try {
            // 3. Validation du panier
            $order = $this->cartService->validateCart($this->getUser());

            return $this->json([
                'success' => true,
                'message' => $this->translator->trans('toast.order_validated'),
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
            throw new \InvalidArgumentException($this->translator->trans('toast.invalid_data'));
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
