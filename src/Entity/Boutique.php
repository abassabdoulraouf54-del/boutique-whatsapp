<?php

namespace App\Entity;

use App\Repository\BoutiqueRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['slug'], message: 'Cette adresse est déjà utilisée par une autre boutique.')]
#[ORM\Entity(repositoryClass: BoutiqueRepository::class)]
class Boutique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Indiquez une adresse pour la boutique.')]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Lettres minuscules, chiffres et tirets uniquement.')]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $couleurPrincipale = null;

    #[ORM\Column(length: 20)]
    private ?string $numeroWhatsapp = null;

    #[ORM\Column(nullable: true)]
    private ?int $fraisLivraison = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slogan = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $momoInfo = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): static { $this->logo = $logo; return $this; }
    public function getCouleurPrincipale(): ?string { return $this->couleurPrincipale; }
    public function setCouleurPrincipale(?string $c): static { $this->couleurPrincipale = $c; return $this; }
    public function getNumeroWhatsapp(): ?string { return $this->numeroWhatsapp; }
    public function setNumeroWhatsapp(string $n): static { $this->numeroWhatsapp = $n; return $this; }
    public function getFraisLivraison(): ?int { return $this->fraisLivraison; }
    public function setFraisLivraison(?int $f): static { $this->fraisLivraison = $f; return $this; }

    public function getSlogan(): ?string { return $this->slogan; }
    public function setSlogan(?string $s): static { $this->slogan = $s; return $this; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $a): static { $this->adresse = $a; return $this; }

    public function getMomoInfo(): ?string { return $this->momoInfo; }
    public function setMomoInfo(?string $m): static { $this->momoInfo = $m; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $t): static { $this->telephone = $t; return $this; }

    public function __toString(): string { return (string) $this->nom; }
}
