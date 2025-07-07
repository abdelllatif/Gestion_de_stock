<?php

namespace App\Entity;

use App\Repository\EntretienRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntretienRepository::class)]
class Entretien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $numero = null;

    /**
     * @var Collection<int, Reparation>
     */
    #[ORM\OneToMany(targetEntity: Reparation::class, mappedBy: 'entretien')]
    private Collection $reparations;

    /**
     * @var Collection<int, Vidange>
     */
    #[ORM\OneToMany(targetEntity: Vidange::class, mappedBy: 'entretien')]
    private Collection $vidanges;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Mecanicien $mecanicien = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Chauffeur $chauffeur = null;

    #[ORM\ManyToOne(inversedBy: 'entretiens')]
    private ?Machine $machine = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chantier $chantier = null;

    public function __construct()
    {
        $this->reparations = new ArrayCollection();
        $this->vidanges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * @return Collection<int, Reparation>
     */
    public function getReparations(): Collection
    {
        return $this->reparations;
    }

    public function addReparation(Reparation $reparation): static
    {
        if (!$this->reparations->contains($reparation)) {
            $this->reparations->add($reparation);
            $reparation->setEntretien($this);
        }

        return $this;
    }

    public function removeReparation(Reparation $reparation): static
    {
        if ($this->reparations->removeElement($reparation)) {
            // set the owning side to null (unless already changed)
            if ($reparation->getEntretien() === $this) {
                $reparation->setEntretien(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Vidange>
     */
    public function getVidanges(): Collection
    {
        return $this->vidanges;
    }

    public function addVidange(Vidange $vidange): static
    {
        if (!$this->vidanges->contains($vidange)) {
            $this->vidanges->add($vidange);
            $vidange->setEntretien($this);
        }

        return $this;
    }

    public function removeVidange(Vidange $vidange): static
    {
        if ($this->vidanges->removeElement($vidange)) {
            // set the owning side to null (unless already changed)
            if ($vidange->getEntretien() === $this) {
                $vidange->setEntretien(null);
            }
        }

        return $this;
    }

    public function getMecanicien(): ?Mecanicien
    {
        return $this->mecanicien;
    }

    public function setMecanicien(?Mecanicien $mecanicien): static
    {
        $this->mecanicien = $mecanicien;

        return $this;
    }

    public function getChauffeur(): ?Chauffeur
    {
        return $this->chauffeur;
    }

    public function setChauffeur(?Chauffeur $chauffeur): static
    {
        $this->chauffeur = $chauffeur;

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
