<?php
// src/Dto/CartDto.php

namespace App\Dto;

use App\Entity\Product;

class CartDto
{
    // Règles commerciales du panier (source unique de vérité)
    public const SHIPPING_THRESHOLD = 49.0;   // livraison offerte à partir de ce montant
    public const SHIPPING_COST = 4.90;        // frais de port sinon
    public const ECO_DISCOUNT_RATE = 0.10;    // réduction éco (-10%)

    private array $items = [];
    private ?int $orderId = null;
    private float $totalAmount = 0.0;

    public function addItem(Product $product, int $quantity = 1): void
    {
        // Vérifier si le produit est déjà dans le panier
        $productId = $product->getId();

        if (isset($this->items[$productId])) {
            $this->items[$productId]['quantity'] += $quantity;
        } else {
            $this->items[$productId] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $product->getPrice(),
                'total_price' => $product->getPrice() * $quantity
            ];
        }

        $this->updateTotalAmount();
    }

    public function removeItem(int $productId): void
    {
        unset($this->items[$productId]);
        $this->updateTotalAmount();
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($productId);
            return;
        }

        if (isset($this->items[$productId])) {
            $this->items[$productId]['quantity'] = $quantity;
            $this->items[$productId]['total_price'] =
                $this->items[$productId]['unit_price'] * $quantity;
        }

        $this->updateTotalAmount();
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getItemCount(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function clear(): void
    {
        $this->items = [];
        $this->totalAmount = 0.0;
        $this->orderId = null;
    }

    public function updateTotalAmount(): void
    {
        $this->totalAmount = array_sum(array_column($this->items, 'total_price'));
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(?int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getItemTotalPrice(int $productId): float
    {
        foreach ($this->items as $item) {
            if ($item['product']->getId() === $productId) {
                return $item['total_price'];
            }
        }
        return 0.0;
    }

    // ****** Récapitulatif (livraison, remise, total) ******

    public function getShippingCost(): float
    {
        if ($this->isEmpty() || $this->totalAmount >= self::SHIPPING_THRESHOLD) {
            return 0.0;
        }

        return self::SHIPPING_COST;
    }

    public function getEcoDiscount(): float
    {
        return round($this->totalAmount * self::ECO_DISCOUNT_RATE, 2);
    }

    public function getFinalTotal(): float
    {
        return round($this->totalAmount + $this->getShippingCost() - $this->getEcoDiscount(), 2);
    }

    /**
     * Montant restant pour bénéficier de la livraison offerte (0 si déjà atteint).
     */
    public function getFreeShippingRemaining(): float
    {
        return max(0.0, round(self::SHIPPING_THRESHOLD - $this->totalAmount, 2));
    }

    /**
     * Récapitulatif complet, utilisé par le template et par l'API (mise à jour temps réel).
     *
     * @return array{subtotal: float, shipping: float, ecoDiscount: float, total: float, count: int, freeShippingRemaining: float}
     */
    public function getSummary(): array
    {
        return [
            'subtotal' => $this->totalAmount,
            'shipping' => $this->getShippingCost(),
            'ecoDiscount' => $this->getEcoDiscount(),
            'total' => $this->getFinalTotal(),
            'count' => $this->getItemCount(),
            'freeShippingRemaining' => $this->getFreeShippingRemaining(),
        ];
    }
}
