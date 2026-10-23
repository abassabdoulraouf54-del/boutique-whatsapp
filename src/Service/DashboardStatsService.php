<?php

namespace App\Service;

use App\Entity\Boutique;
use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;

class DashboardStatsService
{
    /** Statuts exclus du chiffre d'affaires (à adapter à tes valeurs réelles). */
    private const STATUTS_EXCLUS = ['annulee', 'annulée', 'annule', 'annulé'];
    private const SEUIL_STOCK_BAS = 5;

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function collecter(Boutique $boutique, int $jours = 30): array
    {
        $jours = in_array($jours, [7, 30, 90], true) ? $jours : 30;
        $maintenant = new \DateTimeImmutable('now');

        return [
            'jours' => $jours,
            'genere_a' => $maintenant->format('H:i:s'),
            'kpis' => $this->kpis($boutique, $maintenant),
            'serie' => $this->serie($boutique, $jours, $maintenant),
            'statuts' => $this->statuts($boutique),
            'top' => $this->topProduits($boutique, $jours, $maintenant),
            'dernieres' => $this->dernieresCommandes($boutique),
        ];
    }

    private function kpis(Boutique $b, \DateTimeImmutable $now): array
    {
        $jour = $this->totaux($b, $now->setTime(0, 0));
        $mois = $this->totaux($b, $now->modify('first day of this month')->setTime(0, 0));

        return [
            'ca_jour' => $jour['ca'],
            'nb_jour' => $jour['nb'],
            'ca_mois' => $mois['ca'],
            'nb_mois' => $mois['nb'],
            'panier_moyen' => $mois['nb'] > 0 ? (int) round($mois['ca'] / $mois['nb']) : 0,
            'en_attente' => $this->compter(
                'SELECT COUNT(c.id) FROM App\Entity\Commande c WHERE c.boutique = :b AND c.statut = :s',
                ['b' => $b, 's' => 'en_attente']
            ),
            'produits_actifs' => $this->compter(
                'SELECT COUNT(p.id) FROM App\Entity\Produit p WHERE p.boutique = :b AND p.actif = true',
                ['b' => $b]
            ),
            'stock_bas' => $this->compter(
                'SELECT COUNT(p.id) FROM App\Entity\Produit p
                 WHERE p.boutique = :b AND p.actif = true AND p.stock > 0 AND p.stock <= :seuil',
                ['b' => $b, 'seuil' => self::SEUIL_STOCK_BAS]
            ),
            'rupture' => $this->compter(
                'SELECT COUNT(p.id) FROM App\Entity\Produit p
                 WHERE p.boutique = :b AND p.actif = true AND p.stock IS NOT NULL AND p.stock <= 0',
                ['b' => $b]
            ),
        ];
    }

    private function totaux(Boutique $b, \DateTimeImmutable $debut): array
    {
        $r = $this->em->createQuery(
            'SELECT COALESCE(SUM(c.total), 0) AS ca, COUNT(c.id) AS nb
             FROM App\Entity\Commande c
             WHERE c.boutique = :b AND c.createdAt >= :debut AND c.statut NOT IN (:exclus)'
        )
            ->setParameter('b', $b)
            ->setParameter('debut', $debut)
            ->setParameter('exclus', self::STATUTS_EXCLUS)
            ->getSingleResult();

        return ['ca' => (int) $r['ca'], 'nb' => (int) $r['nb']];
    }

    private function serie(Boutique $b, int $jours, \DateTimeImmutable $now): array
    {
        $debut = $now->setTime(0, 0)->modify('-' . ($jours - 1) . ' days');

        $lignes = $this->em->createQuery(
            'SELECT c.createdAt AS date, c.total AS total
             FROM App\Entity\Commande c
             WHERE c.boutique = :b AND c.createdAt >= :debut AND c.statut NOT IN (:exclus)'
        )
            ->setParameter('b', $b)
            ->setParameter('debut', $debut)
            ->setParameter('exclus', self::STATUTS_EXCLUS)
            ->getArrayResult();

        $parJour = [];
        for ($i = 0; $i < $jours; $i++) {
            $parJour[$debut->modify("+$i days")->format('Y-m-d')] = ['ca' => 0, 'nb' => 0];
        }
        foreach ($lignes as $l) {
            $cle = $l['date']->format('Y-m-d');
            if (isset($parJour[$cle])) {
                $parJour[$cle]['ca'] += (int) $l['total'];
                $parJour[$cle]['nb']++;
            }
        }

        $labels = $ca = $nb = [];
        foreach ($parJour as $jour => $v) {
            $labels[] = (new \DateTimeImmutable($jour))->format('d/m');
            $ca[] = $v['ca'];
            $nb[] = $v['nb'];
        }

        return ['labels' => $labels, 'ca' => $ca, 'nb' => $nb, 'total' => array_sum($ca)];
    }

    private function statuts(Boutique $b): array
    {
        $rows = $this->em->createQuery(
            'SELECT c.statut AS statut, COUNT(c.id) AS nb
             FROM App\Entity\Commande c WHERE c.boutique = :b GROUP BY c.statut'
        )->setParameter('b', $b)->getArrayResult();

        return array_map(fn ($r) => ['statut' => $r['statut'], 'nb' => (int) $r['nb']], $rows);
    }

    private function topProduits(Boutique $b, int $jours, \DateTimeImmutable $now): array
    {
        $debut = $now->setTime(0, 0)->modify('-' . ($jours - 1) . ' days');

        $rows = $this->em->createQuery(
            'SELECT p.nom AS nom, SUM(l.quantite) AS qte, SUM(l.quantite * l.prixUnitaire) AS ca
             FROM App\Entity\LigneCommande l
             JOIN l.commande c
             JOIN l.produit p
             WHERE c.boutique = :b AND c.createdAt >= :debut AND c.statut NOT IN (:exclus)
             GROUP BY p.id, p.nom
             ORDER BY qte DESC, ca DESC'
        )
            ->setParameter('b', $b)
            ->setParameter('debut', $debut)
            ->setParameter('exclus', self::STATUTS_EXCLUS)
            ->setMaxResults(5)
            ->getArrayResult();

        return array_map(fn ($r) => [
            'nom' => $r['nom'],
            'qte' => (int) $r['qte'],
            'ca' => (int) $r['ca'],
        ], $rows);
    }

    private function dernieresCommandes(Boutique $b): array
    {
        $commandes = $this->em->getRepository(Commande::class)
            ->findBy(['boutique' => $b], ['createdAt' => 'DESC', 'id' => 'DESC'], 8);

        return array_map(fn (Commande $c) => [
            'id' => $c->getId(),
            'client' => $c->getNomClient(),
            'total' => (int) $c->getTotal(),
            'statut' => $c->getStatut(),
            'date' => $c->getCreatedAt()?->format('d/m H:i'),
        ], $commandes);
    }

    private function compter(string $dql, array $params): int
    {
        $q = $this->em->createQuery($dql);
        foreach ($params as $k => $v) {
            $q->setParameter($k, $v);
        }

        return (int) $q->getSingleScalarResult();
    }
}
