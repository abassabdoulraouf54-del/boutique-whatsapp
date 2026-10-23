<?php

namespace App\Entity;

use App\Repository\LigneCommandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneCommandeRepository::class)]
class LigneCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column]
    private ?int $prixUnitaire = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    public function getId(): ?int { return $this->id; }
    public function getQuantite(): ?int { return $this->quantite; }
    public function setQuantite(int $q): static { $this->quantite = $q; return $this; }
    public function getPrixUnitaire(): ?int { return $this->prixUnitaire; }
    public function setPrixUnitaire(int $p): static { $this->prixUnitaire = $p; return $this; }
    public function getCommande(): ?Commande { return $this->commande; }
    public function setCommande(?Commande $c): static { $this->commande = $c; return $this; }
    public function getProduit(): ?Produit { return $this->produit; }
    public function setProduit(?Produit $p): static { $this->produit = $p; return $this; }
}
