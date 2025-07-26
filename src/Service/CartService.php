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
        private Security $security
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
        $this->persistCart();
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
        if (!$this->cartLoaded) {
            $this->loadCartFromSession();
            $this->cartLoaded = true;
        }
        return $this->cart;
    }

    public function clearCart(): void
    {
        $user = $this->getCurrentUser();
        if ($user) {
            $order = $this->orderRepository->findUnvalidatedOrderByUser($user);
            if ($order) {
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

        $invalidOrder = $this->getInvalidOrder($user);

        $this->syncOrderItems($invalidOrder);

        // Calculer le total
        $totalAmount = 0;
        foreach ($invalidOrder->getOrderItems() as $orderItem) {
            $totalAmount += $orderItem->getTotalPrice();
        }
        $invalidOrder->setTotalAmount($totalAmount);

        // Validation finale
        $validOrder = $invalidOrder->setIsValid(true);
        $validOrder->setOrderNumber($this->generateOrderNumber());

        $this->entityManager->flush();

        // Vider le panier après validation
        $this->clearCart();

        return $validOrder;
    }

    public function persistCart(): void
    {
        if ($this->cart->isEmpty()) {
            return;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            return;
        }

        try {
            $order = $this->orderRepository->findUnvalidatedOrderByUser($user);

            if (!$order) {
                $order = new Order();
                $order->setUser($user);
                $order->setIsValid(false);
                $order->setCreatedAt(new \DateTimeImmutable());
                $order->setTotalAmount(0.0);

                $this->entityManager->persist($order);
                $this->entityManager->flush();
                $this->cart->setOrderId($order->getId());
            }

            $this->syncOrderItems($order);
            $this->entityManager->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $this->entityManager->clear();
            $order = $this->orderRepository->findUnvalidatedOrderByUser($user);
            if ($order) {
                $this->cart->setOrderId($order->getId());
                $this->syncOrderItems($order);
                $this->entityManager->flush();
            }
        } catch (\Exception $e) {
            // Optionnel : log ou gestion d'erreur
        }
    }

    private function getInvalidOrder(User $user): Order
    {
        return $this->orderRepository->findUnvalidatedOrderByUser($user)
            ?? throw new \RuntimeException('Order should exist after persistCart');
    }

    private function syncOrderItems(Order $order): void
    {
        
        $orderItems = $order->getOrderItems()->toArray();
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

            $order->addOrderItem($orderItem);
            $totalAmount += $cartItem['total_price'];
        }

        $order->setTotalAmount($totalAmount);
    }

    private function loadCartFromSession(): void
    {
        if ($this->cartLoaded) {
            return;
        }

        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $this->cart = new CartDto();

        // Charger l'Order non validée si présente
        $orderId = $session->get('cart_order_id');
        if ($orderId) {
            $order = $this->orderRepository->find($orderId);
            if ($order && !$order->isValid()) {
                foreach ($order->getOrderItems() as $orderItem) {
                    $product = $orderItem->getProduct();
                    if ($product) {
                        $this->cart->addItem($product, $orderItem->getQuantity());
                    }
                }
                $this->cart->setOrderId($orderId);
                $this->cartLoaded = true;
                return; // NE PAS charger la session si une commande existe
            }
        }

        // Sinon, charger les produits depuis la session
        $cartData = $session->get('cart', []);
        foreach ($cartData as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $this->cart->addItem($product, $quantity);
            }
        }

        $this->cartLoaded = true;
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
        return $this->security->getUser();
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
