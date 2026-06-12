<?php
// src/Entity/Order.php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'Le montant total est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le montant total doit être positif ou nul.')]
    #[Assert\Range(
        min: 0,
        max: 999999.99,
        notInRangeMessage: 'Le montant total doit être entre {{ min }}€ et {{ max }}€.'
    )]
    private ?string $totalAmount = null;

    #[ORM\Column]
    private ?bool $isValid = false;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le numéro de commande ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9\-_]+$/',
        message: 'Le numéro de commande ne peut contenir que des lettres majuscules, des chiffres, des tirets et des underscores.'
    )]
    private ?string $orderNumber = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'orderRef', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orderItems;

    // --- Paiement Stripe ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    // --- Snapshot de l'adresse de livraison (figée au moment du paiement) ---
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shippingLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shippingLine2 = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingCity = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingCountry = null;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isValid = false;
        $this->orderItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount ? (float) $this->totalAmount : null;
    }

    public function setTotalAmount(string|float $totalAmount): static
    {
        $this->totalAmount = (string) $totalAmount;
        return $this;
    }

    public function isValid(): ?bool
    {
        return $this->isValid; 
    }

    public function getIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): static
    {
        $this->isValid = $isValid;
        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

        return $this;
    }

    // --- Paiement Stripe ---

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function isPaid(): bool
    {
        return $this->paidAt !== null;
    }

    // --- Adresse de livraison (snapshot) ---

    public function getShippingLabel(): ?string
    {
        return $this->shippingLabel;
    }

    public function setShippingLabel(?string $shippingLabel): static
    {
        $this->shippingLabel = $shippingLabel;
        return $this;
    }

    public function getShippingLine1(): ?string
    {
        return $this->shippingLine1;
    }

    public function setShippingLine1(?string $shippingLine1): static
    {
        $this->shippingLine1 = $shippingLine1;
        return $this;
    }

    public function getShippingLine2(): ?string
    {
        return $this->shippingLine2;
    }

    public function setShippingLine2(?string $shippingLine2): static
    {
        $this->shippingLine2 = $shippingLine2;
        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function setShippingPostalCode(?string $shippingPostalCode): static
    {
        $this->shippingPostalCode = $shippingPostalCode;
        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(?string $shippingCity): static
    {
        $this->shippingCity = $shippingCity;
        return $this;
    }

    public function getShippingCountry(): ?string
    {
        return $this->shippingCountry;
    }

    public function setShippingCountry(?string $shippingCountry): static
    {
        $this->shippingCountry = $shippingCountry;
        return $this;
    }

    /**
     * Copie les champs d'une Address dans le snapshot de la commande.
     */
    public function setShippingFromAddress(Address $address): static
    {
        $this->shippingLabel = $address->getLabel();
        $this->shippingLine1 = $address->getLine1();
        $this->shippingLine2 = $address->getLine2();
        $this->shippingPostalCode = $address->getPostalCode();
        $this->shippingCity = $address->getCity();
        $this->shippingCountry = $address->getCountry();
        return $this;
    }
}
