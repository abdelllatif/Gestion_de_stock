<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'stock')]
    private Collection $article;

    #[ORM\Column]
    private ?int $quantite_chantier = null;

    #[ORM\Column]
    private ?int $bon_etat = null;

    #[ORM\Column]
    private ?int $mauvais_etat = null;

    #[ORM\Column]
    private ?int $feraille_etat = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    private ?Chantier $chantier = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    private ?Machine $machine = null;

   

    public function __construct()
    {
        $this->article = new ArrayCollection();
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
            $article->setStock($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->article->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getStock() === $this) {
                $article->setStock(null);
            }
        }

        return $this;
    }

    public function getQuantiteChantier(): ?int
    {
        return $this->quantite_chantier;
    }

    public function setQuantiteChantier(int $quantite_chantier): static
    {
        $this->quantite_chantier = $quantite_chantier;

        return $this;
    }

    public function getBonEtat(): ?int
    {
        return $this->bon_etat;
    }

    public function setBonEtat(int $bon_etat): static
    {
        $this->bon_etat = $bon_etat;

        return $this;
    }

    public function getMauvaisEtat(): ?int
    {
        return $this->mauvais_etat;
    }

    public function setMauvaisEtat(int $mauvais_etat): static
    {
        $this->mauvais_etat = $mauvais_etat;

        return $this;
    }

    public function getFerailleEtat(): ?int
    {
        return $this->feraille_etat;
    }

    public function setFerailleEtat(int $feraille_etat): static
    {
        $this->feraille_etat = $feraille_etat;

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

    public function getMachine(): ?Machine
    {
        return $this->machine;
    }

    public function setMachine(?Machine $machine): static
    {
        $this->machine = $machine;

        return $this;
    }

}
