<?php

namespace App\Entity;

use App\Repository\DemandeDetailsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeDetailsRepository::class)]
class DemandeDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'demandeDetails')]
    private ?DemandeAchat $demandeAchat = null;

    /**
     * L'article demandé
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'demandeDetails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column(length: 255)]
    private ?string $fournisseur = null;

    #[ORM\Column]
    private ?float $prix_unitaire = null;

    #[ORM\Column]
    private ?float $prixTotal = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemandeAchat(): ?DemandeAchat
    {
        return $this->demandeAchat;
    }

    public function setDemandeAchat(?DemandeAchat $demandeAchat): static
    {
        $this->demandeAchat = $demandeAchat;

        return $this;
    }

    /**
     * Retourne l'article demandé
     */
    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

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

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getPrixUnitaire(): ?float
    {
        return $this->prix_unitaire;
    }

    public function setPrixUnitaire(float $prix_unitaire): static
    {
        $this->prix_unitaire = $prix_unitaire;

        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(float $prixTotal): static
    {
        $this->prixTotal = $prixTotal;

        return $this;
    }
}
