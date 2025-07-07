<?php

namespace App\Entity;

use App\Repository\ReparationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReparationRepository::class)]
class Reparation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 255)]
    private ?string $designations = null;

    #[ORM\Column]
    private ?float $montant_ttc = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $observation = null;

    #[ORM\ManyToOne(inversedBy: 'reparations')]
    private ?Entretien $entretien = null;

    #[ORM\Column]
    private ?float $consomation = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDesignations(): ?string
    {
        return $this->designations;
    }

    public function setDesignations(string $designations): static
    {
        $this->designations = $designations;

        return $this;
    }

    public function getMontantTtc(): ?float
    {
        return $this->montant_ttc;
    }

    public function setMontantTtc(float $montant_ttc): static
    {
        $this->montant_ttc = $montant_ttc;

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

    public function getEntretien(): ?Entretien
    {
        return $this->entretien;
    }

    public function setEntretien(?Entretien $entretien): static
    {
        $this->entretien = $entretien;

        return $this;
    }

    public function getConsomation(): ?float
    {
        return $this->consomation;
    }

    public function setConsomation(float $consomation): static
    {
        $this->consomation = $consomation;

        return $this;
    }
}
