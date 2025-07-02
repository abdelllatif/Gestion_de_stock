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
    private ?string $km = null;

    #[ORM\Column(length: 255)]
    private ?string $type_changment = null;

    #[ORM\Column]
    private ?float $km_prochaine_vidange = null;

    #[ORM\Column]
    private ?float $huil_filter_change = null;

    #[ORM\Column]
    private ?float $gasoil_filter_change = null;

    #[ORM\Column]
    private ?float $air_filter_change = null;

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

    public function getKm(): ?string
    {
        return $this->km;
    }

    public function setKm(string $km): static
    {
        $this->km = $km;

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

    public function getKmProchaineVidange(): ?float
    {
        return $this->km_prochaine_vidange;
    }

    public function setKmProchaineVidange(float $km_prochaine_vidange): static
    {
        $this->km_prochaine_vidange = $km_prochaine_vidange;

        return $this;
    }

    public function getHuilFilterChange(): ?float
    {
        return $this->huil_filter_change;
    }

    public function setHuilFilterChange(float $huil_filter_change): static
    {
        $this->huil_filter_change = $huil_filter_change;

        return $this;
    }

    public function getGasoilFilterChange(): ?float
    {
        return $this->gasoil_filter_change;
    }

    public function setGasoilFilterChange(float $gasoil_filter_change): static
    {
        $this->gasoil_filter_change = $gasoil_filter_change;

        return $this;
    }

    public function getAirFilterChange(): ?float
    {
        return $this->air_filter_change;
    }

    public function setAirFilterChange(float $air_filter_change): static
    {
        $this->air_filter_change = $air_filter_change;

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
