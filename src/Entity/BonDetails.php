<?php

namespace App\Entity;

use App\Repository\BonDetailsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonDetailsRepository::class)]
class BonDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fournisseur = null;

    #[ORM\Column(length: 255)]
    private ?string $unite = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\ManyToOne(inversedBy: 'bonDetails')]
    private ?Bon $bon = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): static
    {
        $this->unite = $unite;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getBon(): ?Bon
    {
        return $this->bon;
    }

    public function setBon(?Bon $bon): static
    {
        $this->bon = $bon;

        return $this;
    }
}
