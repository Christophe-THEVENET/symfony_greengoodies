<?php
// src/Entity/OrderItem.php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productImage = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être positive.')]
    #[Assert\Range(
        min: 1,
        max: 999,
        notInRangeMessage: 'La quantité doit être entre {{ min }} et {{ max }}.'
    )]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    #[Assert\NotNull(message: 'Le prix unitaire est obligatoire.')]
    #[Assert\Positive(message: 'Le prix unitaire doit être positif.')]
    #[Assert\Range(
        min: 0.01,
        max: 9999.99,
        notInRangeMessage: 'Le prix unitaire doit être entre {{ min }}€ et {{ max }}€.'
    )]
    private ?string $unitPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'Le prix total est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le prix total doit être positif ou nul.')]
    private ?string $totalPrice = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    private ?Order $orderRef = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getProductImage(): ?string
    {
        return $this->productImage;
    }

    public function setProductImage(?string $productImage): static
    {
        $this->productImage = $productImage;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice ? (float) $this->unitPrice : null;
    }

    public function setUnitPrice(string|float $unitPrice): static
    {
        $this->unitPrice = (string) $unitPrice;
        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice ? (float) $this->totalPrice : null;
    }

    public function setTotalPrice(string|float $totalPrice): static
    {
        $this->totalPrice = (string) $totalPrice;
        return $this;
    }

    public function __toString(): string
    {
        return $this->productName . ' (x' . $this->quantity . ')';
    }

    public function getOrder(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrder(?Order $order): static
    {
        $this->orderRef = $order;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }
}
