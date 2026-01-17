<?php
// src/Service/CartService.php

namespace App\Service;

use App\Dto\CartDto;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private CartDto $cart;
    private bool $cartLoaded = false;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private RequestStack $requestStack,
        private Security $security,
        LoggerInterface $logger = null
    ) {
        $this->cart = new CartDto();
        $this->loadCartFromSession();
    }

    public function addProduct(int $productId, int $quantity = 1): void
    {
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new \InvalidArgumentException('Produit non trouvé');
        }

        $this->cart->addItem($product, $quantity);
        $this->persistCart();
        $this->saveCartToSession();
    }

    public function removeProduct(int $productId): void
    {
        $this->loadCartFromSession();
        $this->cart->removeItem($productId);

        if ($this->cart->isEmpty()) {
            // Supprimer l'Order non validé si le panier est vide
            $this->cleanupEmptyOrder();
        } else {
            $this->persistCart();
        }

        $this->saveCartToSession();
    }

    public function updateQuantity(int $productId, int $quantity): int
    {
        $this->loadCartFromSession();
        $this->cart->updateQuantity($productId, $quantity);
        $this->persistCart();
        $this->saveCartToSession();
        return $quantity;
    }

    public function getCart(): CartDto
    {
        if (! $this->cartLoaded) {
            $this->loadCartFromSession();
            $this->cartLoaded = true;
        }

        return $this->cart;
    }

    /**
     * Force le rechargement du panier depuis la session.
     * Utile après un login pour récupérer le panier anonyme.
     */
    public function forceReloadFromSession(): void
    {
        $this->cartLoaded = false;
        $this->cart = new CartDto();
        $this->loadCartFromSession();
    }

    public function clearCart(): void
    {
        $this->cleanupEmptyOrder();
        $this->cart->clear();
        $this->saveCartToSession();
    }

    public function validateCart(User $user): Order
    {
        if ($this->cart->isEmpty()) {
            throw new \InvalidArgumentException('Le panier est vide');
        }

        // Récupérer la commande non validée ou générer une erreur
        $order = $this->orderRepository->findUnvalidatedOrderByUser($user) ?? throw new \RuntimeException('Commande introuvable');

        // Synchroniser une dernière fois avec le panier
        $this->syncOrderItems($order);

        // Finaliser la commande
        $order->setIsValid(true);
        $order->setOrderNumber($this->generateOrderNumber());

        $this->entityManager->flush();

        // Vider le panier après validation
        $this->clearCart();

        return $order;
    }

    public function persistCart(): void
    {
        // Ne rien faire si panier vide ou utilisateur non connecté
        if ($this->cart->isEmpty() || ! ($user = $this->getCurrentUser())) {
            return;
        }

        try {
            // Récupérer ou créer la commande
            $order = $this->getOrCreateOrder($user);

            // Synchroniser les items
            $this->syncOrderItems($order);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Log l'exception avec LoggerInterface
            if (isset($logger)) {
                $logger->error('Erreur lors de la persistance du panier : ' . $e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        }
    }

    private function getOrCreateOrder(User $user): Order
    {
        $order = $this->orderRepository->findUnvalidatedOrderByUser($user);

        if (! $order) {
            $order = new Order();
            $order->setUser($user);
            $order->setIsValid(false);
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setTotalAmount(0.0);

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->cart->setOrderId($order->getId());
        }
        return $order;
    }

    private function syncOrderItems(Order $order): void
    {
        // Nettoie tous les items existants
        foreach ($order->getOrderItems()->toArray() as $item) {
            $order->removeOrderItem($item);
        }

        // Ajouter les nouveaux items depuis le panier
        $totalAmount = 0;
        foreach ($this->cart->getItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProductName($cartItem['product']->getName());
            $orderItem->setProductImage($cartItem['product']->getImageFilename());
            $orderItem->setQuantity($cartItem['quantity']);
            $orderItem->setUnitPrice($cartItem['unit_price']);
            $orderItem->setTotalPrice($cartItem['total_price']);
            $orderItem->setProduct($cartItem['product']);

            $order->addOrderItem($orderItem);
            $totalAmount += $cartItem['total_price'];
        }

        $order->setTotalAmount($totalAmount);
    }

    // Evite chargements multiples -> tente commande existante, sinon session
    private function loadCartFromSession(): void
    {
        if ($this->cartLoaded) {
            return;
        }

        $session = $this->getSession();
        if (! $session) {
            $this->cartLoaded = true;
            return;
        }

        $this->cart = new CartDto();

        // Charge depuis une commande existante si possible
        if ($this->loadCartFromOrder($session)) {
            return;
        }

        // Sinon charge depuis les données de session
        $this->loadCartFromSessionData($session);
    }

    private function loadCartFromOrder(SessionInterface $session): bool
    {
        $orderId = $session->get('cart_order_id');
        if (! $orderId) {
            return false;
        }

        $order = $this->orderRepository->find($orderId);
        if (! $order || $order->isValid()) {
            $session->remove('cart_order_id');
            return false;
        }

        foreach ($order->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            if ($product) {
                $this->cart->addItem($product, $orderItem->getQuantity());
            }
        }

        $this->cart->setOrderId($orderId);
        $this->cartLoaded = true;
        return true;
    }

    private function loadCartFromSessionData(SessionInterface $session): void
    {
        $cartData = $session->get('cart', []);
        foreach ($cartData as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $this->cart->addItem($product, $quantity);
            }
        }

        $this->cartLoaded = true;
    }

    private function cleanupEmptyOrder(): void
    {

        $user = $this->getCurrentUser();
        if (! $user) {
            return;
        }

        $order = $this->orderRepository->findUnvalidatedOrderByUser($user);
        if ($order) {
            $this->entityManager->remove($order);
            $this->entityManager->flush();
        }
    }

    // light data persistence in session
    private function saveCartToSession(): void
    {
        $session = $this->getSession();
        if (! $session) {
            return;
        }

        $cartData = [];
        foreach ($this->cart->getItems() as $productId => $item) {
            $cartData[$productId] = $item['quantity'];
        }

        $session->set('cart', $cartData);
        $session->set('cart_order_id', $this->cart->getOrderId());
    }

    private function getCurrentUser(): ?User
    {
        return $this->security->getUser();
    }

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getSession();
    }

    private function generateOrderNumber(): string
    {
        return 'CMD-' . str_pad(
            $this->orderRepository->getNextOrderNumber(),
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
