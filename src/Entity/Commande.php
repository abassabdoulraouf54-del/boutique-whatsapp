<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomClient = null;

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'text')]
    private ?string $adresse = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'en_attente';

    #[ORM\Column]
    private ?int $total = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Boutique $boutique = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: LigneCommande::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $lignes;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $modePaiement = 'livraison';

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getNomClient(): ?string { return $this->nomClient; }
    public function setNomClient(string $n): static { $this->nomClient = $n; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(string $t): static { $this->telephone = $t; return $this; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(string $a): static { $this->adresse = $a; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $s): static { $this->statut = $s; return $this; }
    public function getTotal(): ?int { return $this->total; }
    public function setTotal(int $t): static { $this->total = $t; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $d): static { $this->createdAt = $d; return $this; }
    public function getBoutique(): ?Boutique { return $this->boutique; }
    public function setBoutique(?Boutique $b): static { $this->boutique = $b; return $this; }

    public function getModePaiement(): ?string { return $this->modePaiement; }
    public function setModePaiement(?string $m): static { $this->modePaiement = $m; return $this; }

    /** @return Collection<int, LigneCommande> */
    public function getLignes(): Collection { return $this->lignes; }

    public function addLigne(LigneCommande $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }
        return $this;
    }

    public function removeLigne(LigneCommande $ligne): static
    {
        $this->lignes->removeElement($ligne);
        return $this;
    }
}
