<?php

namespace App\Entity;

use App\Repository\OrderProductsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderProductsRepository::class)]
class OrderProducts
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderedProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $orderedProducts = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderedProducts(): ?Order
    {
        return $this->orderedProducts;
    }

    public function setOrderedProducts(?Order $orderedProducts): static
    {
        $this->orderedProducts = $orderedProducts;

        return $this;
    }
}
