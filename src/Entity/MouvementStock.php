<?php

namespace App\Entity;

use App\Repository\MouvementStockRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementStockRepository::class)]
class MouvementStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fournisseur = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementStocks')]
    private ?Chantier $chantierExp = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementStocks')]
    private ?Chantier $chantierRec = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementStocks')]
    private ?Article $article = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementStocks')]
    private ?Machine $machine = null;

    #[ORM\Column(nullable: true)]
    private ?int $bon_etat = null;

    #[ORM\Column(nullable: true)]
    private ?int $mauvais_etat = null;

    #[ORM\Column(nullable: true)]
    private ?int $feraille_etat = null;

    #[ORM\Column(length: 255, options: ["default" => "waiting"])]
    private ?string $status = 'waiting';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;
        return $this;
    }

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getChantierExp(): ?Chantier
    {
        return $this->chantierExp;
    }

    public function setChantierExp(?Chantier $chantierExp): static
    {
        $this->chantierExp = $chantierExp;
        return $this;
    }

    public function getChantierRec(): ?Chantier
    {
        return $this->chantierRec;
    }

    public function setChantierRec(?Chantier $chantierRec): static
    {
        $this->chantierRec = $chantierRec;
        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;
        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getMachine(): ?Machine
    {
        return $this->machine;
    }

    public function setMachine(?Machine $machine): static
    {
        $this->machine = $machine;
        return $this;
    }

    public function getBonEtat(): ?int
    {
        return $this->bon_etat;
    }

    public function setBonEtat(?int $bon_etat): static
    {
        $this->bon_etat = $bon_etat;
        return $this;
    }

    public function getMauvaisEtat(): ?int
    {
        return $this->mauvais_etat;
    }

    public function setMauvaisEtat(?int $mauvais_etat): static
    {
        $this->mauvais_etat = $mauvais_etat;
        return $this;
    }

    public function getFerailleEtat(): ?int
    {
        return $this->feraille_etat;
    }

    public function setFerailleEtat(?int $feraille_etat): static
    {
        $this->feraille_etat = $feraille_etat;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
}