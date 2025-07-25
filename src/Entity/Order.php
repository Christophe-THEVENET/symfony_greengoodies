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

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'orderRef', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orderItems;


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
}
