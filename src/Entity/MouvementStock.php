<?php

namespace App\Entity;

use App\Repository\MouvementStockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementStockRepository::class)]
class MouvementStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'mouvementStock')]
    private Collection $article;

    /**
     * @var Collection<int, Machine>
     */
    #[ORM\OneToMany(targetEntity: Machine::class, mappedBy: 'mouvementStock')]
    private Collection $machine;

    #[ORM\Column(length: 255)]
    private ?string $recepteur = null;

    #[ORM\Column(length: 255)]
    private ?string $expediteur = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $observation = null;

    #[ORM\Column(length: 255)]
    private ?string $fournisseur = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementStocks')]
    private ?Chantier $chantier = null;

    public function __construct()
    {
        $this->article = new ArrayCollection();
        $this->machine = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticle(): Collection
    {
        return $this->article;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->article->contains($article)) {
            $this->article->add($article);
            $article->setMouvementStock($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->article->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getMouvementStock() === $this) {
                $article->setMouvementStock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Machine>
     */
    public function getMachine(): Collection
    {
        return $this->machine;
    }

    public function addMachine(Machine $machine): static
    {
        if (!$this->machine->contains($machine)) {
            $this->machine->add($machine);
            $machine->setMouvementStock($this);
        }

        return $this;
    }

    public function removeMachine(Machine $machine): static
    {
        if ($this->machine->removeElement($machine)) {
            // set the owning side to null (unless already changed)
            if ($machine->getMouvementStock() === $this) {
                $machine->setMouvementStock(null);
            }
        }

        return $this;
    }

    public function getRecepteur(): ?string
    {
        return $this->recepteur;
    }

    public function setRecepteur(string $recepteur): static
    {
        $this->recepteur = $recepteur;

        return $this;
    }

    public function getExpediteur(): ?string
    {
        return $this->expediteur;
    }

    public function setExpediteur(string $expediteur): static
    {
        $this->expediteur = $expediteur;

        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(string $observation): static
    {
        $this->observation = $observation;

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

    public function getChantier(): ?Chantier
    {
        return $this->chantier;
    }

    public function setChantier(?Chantier $chantier): static
    {
        $this->chantier = $chantier;

        return $this;
    }
}
