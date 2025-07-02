<?php

namespace App\Entity;

use App\Repository\BonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonRepository::class)]
class Bon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $numero_serie = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    /**
     * @var Collection<int, BonDetails>
     */
    #[ORM\OneToMany(targetEntity: BonDetails::class, mappedBy: 'bon')]
    private Collection $bonDetails;

    #[ORM\ManyToOne(inversedBy: 'bons')]
    private ?Chantier $chantier = null;

    public function __construct()
    {
        $this->bonDetails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNumeroSerie(): ?string
    {
        return $this->numero_serie;
    }

    public function setNumeroSerie(string $numero_serie): static
    {
        $this->numero_serie = $numero_serie;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;

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

    /**
     * @return Collection<int, BonDetails>
     */
    public function getBonDetails(): Collection
    {
        return $this->bonDetails;
    }

    public function addBonDetail(BonDetails $bonDetail): static
    {
        if (!$this->bonDetails->contains($bonDetail)) {
            $this->bonDetails->add($bonDetail);
            $bonDetail->setBon($this);
        }

        return $this;
    }

    public function removeBonDetail(BonDetails $bonDetail): static
    {
        if ($this->bonDetails->removeElement($bonDetail)) {
            // set the owning side to null (unless already changed)
            if ($bonDetail->getBon() === $this) {
                $bonDetail->setBon(null);
            }
        }

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
