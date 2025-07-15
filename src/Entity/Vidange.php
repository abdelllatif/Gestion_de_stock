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
    
    #[ORM\Column(nullable: true)]
    private ?int $kilometre = null;

    // Champs supprimés: type_huile et quantite

    #[ORM\Column(nullable: true)]
    private ?int $prochaineVidange = null;

    #[ORM\Column(nullable: true)]
    private ?int $km_filtre_huile = null;

    #[ORM\Column(nullable: true)]
    private ?int $km_filtre_gasoil = null;

    #[ORM\Column(nullable: true)]
    private ?int $km_filtre_air = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

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
    
    public function getKilometre(): ?int
    {
        return $this->kilometre;
    }

    public function setKilometre(?int $kilometre): static
    {
        $this->kilometre = $kilometre;

        return $this;
    }

    // Méthodes getTypeHuile, setTypeHuile, getQuantite, setQuantite supprimées

    public function getProchaineVidange(): ?int
    {
        return $this->prochaineVidange;
    }

    public function setProchaineVidange(?int $prochaineVidange): static
    {
        $this->prochaineVidange = $prochaineVidange;

        return $this;
    }

    public function getKmFiltreHuile(): ?int
    {
        return $this->km_filtre_huile;
    }

    public function setKmFiltreHuile(?int $km_filtre_huile): static
    {
        $this->km_filtre_huile = $km_filtre_huile;

        return $this;
    }

    public function getKmFiltreGasoil(): ?int
    {
        return $this->km_filtre_gasoil;
    }

    public function setKmFiltreGasoil(?int $km_filtre_gasoil): static
    {
        $this->km_filtre_gasoil = $km_filtre_gasoil;

        return $this;
    }

    public function getKmFiltreAir(): ?int
    {
        return $this->km_filtre_air;
    }

    public function setKmFiltreAir(?int $km_filtre_air): static
    {
        $this->km_filtre_air = $km_filtre_air;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
