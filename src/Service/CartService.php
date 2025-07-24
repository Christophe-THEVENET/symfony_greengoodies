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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private CartDto $cart;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private RequestStack $requestStack
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
        $this->cart->removeItem($productId);
        $this->persistCart();
        $this->saveCartToSession();
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $this->cart->updateQuantity($productId, $quantity);
        $this->persistCart();
        $this->saveCartToSession();
    }


    public function getCart(): CartDto
    {
        return $this->cart;
    }

    public function clearCart(): void
    {
        // Supprimer la commande non validée si elle existe
        if ($this->cart->getOrderId()) {
            $order = $this->orderRepository->find($this->cart->getOrderId());
            if ($order && !$order->isValid()) {
                $this->entityManager->remove($order);
                $this->entityManager->flush();
            }
        }

        $this->cart->clear();
        $this->saveCartToSession();
    }

    public function validateCart(User $user): Order
    {
        if ($this->cart->isEmpty()) {
            throw new \InvalidArgumentException('Le panier est vide');
        }

        $order = $this->getOrCreateOrder($user);
        $order->setIsValid(true);
        $order->setOrderNumber($this->generateOrderNumber());

        $this->entityManager->flush();

        // Vider le panier après validation
        $this->cart->clear();
        $this->saveCartToSession();

        return $order;
    }

    private function persistCart(): void
    {
        if ($this->cart->isEmpty()) {
            return;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            return; // Panier anonyme, on ne persiste pas
        }

        $order = $this->getOrCreateOrder($user);
        $this->syncOrderItems($order);

        $this->entityManager->flush();
        $this->cart->setOrderId($order->getId());
    }

    private function getOrCreateOrder(User $user): Order
    {
        // Chercher une commande non validée existante
        $order = $this->orderRepository->findUnvalidatedOrderByUser($user);

        if (!$order) {
            $order = new Order();
            $order->setUser($user);
            $order->setIsValid(false);
            $order->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($order);
        }

        return $order;
    }

    private function syncOrderItems(Order $order): void
    {
        // ✅ MEILLEURE APPROCHE : utiliser removeOrderItem()
        $orderItems = $order->getOrderItems()->toArray(); // Copie pour éviter les modifications pendant l'itération
        foreach ($orderItems as $item) {
            $order->removeOrderItem($item);  // Gère la relation + suppression
            $this->entityManager->remove($item);  // Supprime de la BDD
        }

        // Ou encore plus simple avec orphanRemoval: true :
        foreach ($orderItems as $item) {
            $order->removeOrderItem($item);  // orphanRemoval supprime automatiquement
        }

        // Ajouter les nouveaux items
        $totalAmount = 0;
        foreach ($this->cart->getItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProductName($cartItem['product']->getName());
            $orderItem->setProductImage($cartItem['product']->getImageFilename());
            $orderItem->setQuantity($cartItem['quantity']);
            $orderItem->setUnitPrice($cartItem['unit_price']);
            $orderItem->setTotalPrice($cartItem['total_price']);
            $orderItem->setProduct($cartItem['product']);

            $order->addOrderItem($orderItem);  // ✅ Utilise la méthode de l'entité
            $totalAmount += $cartItem['total_price'];
        }

        $order->setTotalAmount($totalAmount);
    }

    private function loadCartFromSession(): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        // get cart data from session
        // [[idProduct => quantity], [idProduct => quantity]]
        $cartData = $session->get('cart', []);

        // hydrate Cart DTO
        // [$items => [idProduct => [product => Product, quantity => int, unit_price => float, total_price => float]], $orderId => int|null, totalAmount => float]
        foreach ($cartData as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $this->cart->addItem($product, $quantity);
            }
        }

        // Charger l'ordre ID depuis la session
        $orderId = $session->get('cart_order_id');
        if ($orderId) {
            $this->cart->setOrderId($orderId);
        }
    }

    // light data persistence in session
    private function saveCartToSession(): void
    {
        $session = $this->getSession();
        if (!$session) {
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
        // Implémentation pour récupérer l'utilisateur connecté
        // Adapter selon votre système d'authentification
        return null;
    }

    private function getSession(): ?SessionInterface
    {
        // Récupère la requête HTTP actuelle depuis la pile de requêtes
        $request = $this->requestStack->getCurrentRequest();
        // Retourne la session associée à cette requête (ou null si pas de requête)
        return $request?->getSession();
    }

    private function generateOrderNumber(): string
    {
        return 'CMD-' . date('Y') . '-' . str_pad(
            $this->orderRepository->getNextOrderNumber(),
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
