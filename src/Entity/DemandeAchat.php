<?php

namespace App\Entity;

use App\Repository\DemandeAchatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeAchatRepository::class)]
class DemandeAchat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 255)]
    private ?string $etat = null;

    #[ORM\Column]
    private ?float $montantHT = null;

    #[ORM\Column]
    private ?float $montantTTC = null;

    #[ORM\Column]
    private ?int $tva = null;

    #[ORM\ManyToOne(inversedBy: 'demandeAchats')]
    private ?User $uilisateur = null;

    /**
     * @var Collection<int, DemandeDetails>
     */
    #[ORM\OneToMany(targetEntity: DemandeDetails::class, mappedBy: 'demandeAchat')]
    private Collection $demandeDetails;

    public function __construct()
    {
        $this->demandeDetails = new ArrayCollection();
    }

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

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getMontantHT(): ?float
    {
        return $this->montantHT;
    }

    public function setMontantHT(float $montantHT): static
    {
        $this->montantHT = $montantHT;

        return $this;
    }

    public function getMontantTTC(): ?float
    {
        return $this->montantTTC;
    }

    public function setMontantTTC(float $montantTTC): static
    {
        $this->montantTTC = $montantTTC;

        return $this;
    }

    public function getTva(): ?int
    {
        return $this->tva;
    }

    public function setTva(int $tva): static
    {
        $this->tva = $tva;

        return $this;
    }

    public function getUilisateur(): ?User
    {
        return $this->uilisateur;
    }

    public function setUilisateur(?User $uilisateur): static
    {
        $this->uilisateur = $uilisateur;

        return $this;
    }

    /**
     * @return Collection<int, DemandeDetails>
     */
    public function getDemandeDetails(): Collection
    {
        return $this->demandeDetails;
    }

    public function addDemandeDetail(DemandeDetails $demandeDetail): static
    {
        if (!$this->demandeDetails->contains($demandeDetail)) {
            $this->demandeDetails->add($demandeDetail);
            $demandeDetail->setDemandeAchat($this);
        }

        return $this;
    }

    public function removeDemandeDetail(DemandeDetails $demandeDetail): static
    {
        if ($this->demandeDetails->removeElement($demandeDetail)) {
            // set the owning side to null (unless already changed)
            if ($demandeDetail->getDemandeAchat() === $this) {
                $demandeDetail->setDemandeAchat(null);
            }
        }

        return $this;
    }
}
