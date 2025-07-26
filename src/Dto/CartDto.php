<?php
// src/Dto/CartDto.php

namespace App\Dto;

use App\Entity\Product;

class CartDto
{
    
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

    // Getters/Setters pour orderId
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
                return $item['total_price']; // ou $item['unit_price'] * $item['quantity']
            }
        }
        return 0.0;
    }
}
