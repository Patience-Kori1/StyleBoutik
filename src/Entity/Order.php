<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $adress = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?City $city = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $payOnDelivery = null;

    /**
     * @var Collection<int, OrderProducts>
     */
    #[ORM\OneToMany(targetEntity: OrderProducts::class, mappedBy: 'orderedProducts', orphanRemoval: true)]
    private Collection $orderedProducts;

    #[ORM\Column]
    private ?float $totalPrice = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCompleted = null;

    public function __construct()
    {
        $this->orderedProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(string $adress): static
    {
        $this->adress = $adress;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

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

    public function isPayOnDelivery(): ?bool
    {
        return $this->payOnDelivery;
    }

    public function setPayOnDelivery(bool $payOnDelivery): static
    {
        $this->payOnDelivery = $payOnDelivery;

        return $this;
    }

    /**
     * @return Collection<int, OrderProducts>
     */
    public function getOrderedProducts(): Collection
    {
        return $this->orderedProducts;
    }

    public function addOrderedProduct(OrderProducts $orderedProduct): static
    {
        if (!$this->orderedProducts->contains($orderedProduct)) {
            $this->orderedProducts->add($orderedProduct);
            $orderedProduct->setOrderedProducts($this);
        }

        return $this;
    }

    public function removeOrderedProduct(OrderProducts $orderedProduct): static
    {
        if ($this->orderedProducts->removeElement($orderedProduct)) {
            // set the owning side to null (unless already changed)
            if ($orderedProduct->getOrderedProducts() === $this) {
                $orderedProduct->setOrderedProducts(null);
            }
        }

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(?bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }
}
