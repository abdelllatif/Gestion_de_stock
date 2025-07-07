<?php

namespace App\Entity;

use App\Repository\VidangeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VidangeRepository::class)]
class Vidange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 255)]
    private ?string $consomation = null;

    #[ORM\Column(length: 255)]
    private ?string $type_changment = null;

    #[ORM\Column]
    private ?float $conso_prochaine_vidange = null;

    #[ORM\Column]
    private ?float $prochaine_filter_change = null;

    #[ORM\Column]
    private ?float $montant_ttc = null;

    #[ORM\ManyToOne(inversedBy: 'vidanges')]
    private ?Entretien $entretien = null;

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

    public function getConsomation(): ?string
    {
        return $this->consomation;
    }

    public function setConsomation(string $consomation): static
    {
        $this->consomation = $consomation;

        return $this;
    }

    public function getTypeChangment(): ?string
    {
        return $this->type_changment;
    }

    public function setTypeChangment(string $type_changment): static
    {
        $this->type_changment = $type_changment;

        return $this;
    }

    public function getConsoProchaineVidange(): ?float
    {
        return $this->conso_prochaine_vidange;
    }

    public function setConsoProchaineVidange(float $conso_prochaine_vidange): static
    {
        $this->conso_prochaine_vidange = $conso_prochaine_vidange;

        return $this;
    }

    public function getProchaineFilterChange(): ?float
    {
        return $this->prochaine_filter_change;
    }

    public function setProchaineFilterChange(float $prochaine_filter_change): static
    {
        $this->prochaine_filter_change = $prochaine_filter_change;

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

    public function getEntretien(): ?Entretien
    {
        return $this->entretien;
    }

    public function setEntretien(?Entretien $entretien): static
    {
        $this->entretien = $entretien;

        return $this;
    }
}
