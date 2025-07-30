<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom du produit doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom du produit ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description courte ne peut pas être vide.')]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: 'La description courte doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La description courte ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $shortDescription = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le prix est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être positif.')]
    #[Assert\Range(
        min: 0.01,
        max: 9999.99,
        notInRangeMessage: 'Le prix doit être entre {{ min }}€ et {{ max }}€.'
    )]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du fichier image est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le nom du fichier image ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/\.(jpg|jpeg|png|gif|webp)$/i',
        message: 'Le fichier image doit avoir une extension valide (jpg, jpeg, png, gif, webp).'
    )]
    private ?string $imageFilename = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description longue ne peut pas être vide.')]
    #[Assert\Length(
        min: 50,
        max: 5000,
        minMessage: 'La description longue doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La description longue ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $longDescription = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    private Collection $orderItems;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    public function setLongDescription(string $longDescription): static
    {
        $this->longDescription = $longDescription;

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
            $orderItem->setProduct($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getProduct() === $this) {
                $orderItem->setProduct(null);
            }
        }

        return $this;
    }
}
