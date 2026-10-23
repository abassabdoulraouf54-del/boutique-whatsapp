<?php

namespace App\Controller;

use App\Entity\Boutique;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Repository\BoutiqueRepository;
use App\Repository\CategorieRepository;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ShopController extends AbstractController
{
    #[Route('/', name: 'shop_home')]
    public function home(BoutiqueRepository $boutiques): Response
    {
        $b = $boutiques->findOneBy([], ['id' => 'ASC']);
        if (!$b) {
            return new Response('Aucune boutique configurée.', 404);
        }
        return $this->redirectToRoute('shop_catalogue', ['slug' => $b->getSlug()]);
    }

    #[Route('/b/{slug}', name: 'shop_catalogue')]
    public function catalogue(string $slug, Request $request, BoutiqueRepository $boutiques, CategorieRepository $categories, ProduitRepository $produits): Response
    {
        $b = $this->trouverBoutique($slug, $boutiques);
        $catId = $request->query->getInt('cat');
        $q = trim($request->query->getString('q'));

        $qb = $produits->createQueryBuilder('p')
            ->andWhere('p.boutique = :b')
            ->andWhere('p.actif = true')
            ->setParameter('b', $b)
            ->orderBy('p.nom', 'ASC');
        if ($catId) {
            $qb->andWhere('p.categorie = :c')->setParameter('c', $catId);
        }
        if ($q !== '') {
            $qb->andWhere('LOWER(p.nom) LIKE :q')
               ->setParameter('q', '%' . addcslashes(mb_strtolower($q), '%_\\') . '%');
        }

        return $this->render('shop/catalogue.html.twig', [
            'boutique' => $b,
            'categories' => $categories->findBy(['boutique' => $b], ['nom' => 'ASC']),
            'produits' => $qb->getQuery()->getResult(),
            'cat' => $catId,
            'q' => $q,
        ]);
    }

    #[Route('/b/{slug}/panier', name: 'shop_panier')]
    public function panier(string $slug, Request $request, BoutiqueRepository $boutiques, ProduitRepository $produits): Response
    {
        $b = $this->trouverBoutique($slug, $boutiques);
        [$lignes, $sousTotal] = $this->lignes($request->getSession()->get($this->cle($slug), []), $produits, $b);
        $frais = $b->getFraisLivraison() ?? 0;
        return $this->render('shop/panier.html.twig', [
            'boutique' => $b,
            'lignes' => $lignes,
            'sousTotal' => $sousTotal,
            'frais' => $frais,
            'total' => $sousTotal + $frais,
        ]);
    }

    #[Route('/b/{slug}/panier/ajouter/{id}', name: 'shop_panier_ajouter', methods: ['POST'])]
    public function ajouter(string $slug, int $id, Request $request, BoutiqueRepository $boutiques, ProduitRepository $produits): Response
    {
        $b = $this->trouverBoutique($slug, $boutiques);
        if (!$this->isCsrfTokenValid('panier', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $p = $produits->find($id);
        if (!$p || $p->getBoutique()?->getId() !== $b->getId() || !$p->isActif() || $p->getStock() < 1) {
            $this->addFlash('error', 'Produit indisponible.');
            return $this->redirectToRoute('shop_catalogue', ['slug' => $slug]);
        }
        $session = $request->getSession();
        $panier = $session->get($this->cle($slug), []);
        $panier[$id] = min(($panier[$id] ?? 0) + 1, $p->getStock());
        $session->set($this->cle($slug), $panier);
        $this->addFlash('success', $p->getNom() . ' ajouté au panier.');
        return $this->redirectToRoute('shop_catalogue', ['slug' => $slug]);
    }

    #[Route('/b/{slug}/produit/{id}', name: 'shop_produit', requirements: ['id' => '\d+'])]
    public function produit(string $slug, int $id, BoutiqueRepository $boutiques, ProduitRepository $produits): Response
    {
        $b = $this->trouverBoutique($slug, $boutiques);
        $p = $produits->find($id);
        if (!$p || !$p->isActif() || $p->getBoutique()?->getId() !== $b->getId()) {
            throw $this->createNotFoundException();
        }
        return $this->render('shop/produit.html.twig', ['boutique' => $b, 'produit' => $p]);
    }

    #[Route('/b/{slug}/panier/quantite/{id}', name: 'shop_panier_qte', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function quantite(string $slug, int $id, Request $request, BoutiqueRepository $boutiques, ProduitRepository $produits): Response
    {
        $b = $this->trouverBoutique($slug, $boutiques);
        if (!$this->isCsrfTokenValid('panier', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $delta = $request->request->getInt('delta') >= 0 ? 1 : -1;
        $session = $request->getSession();
        $panier = $session->get($this->cle($slug), []);
        $p = $produits->find($id);
        if (isset($panier[$id]) && $p && $p->getBoutique()?->getId() === $b->getId()) {
            $n = $panier[$id] + $delta;
            if ($n < 1) {
                unset($panier[$id]);
            } else {
                $panier[$id] = min($n, max($p->getStock(), 1));
            }
            $session->set($this->cle($slug), $panier);
        }
        return $this->redirectToRoute('shop_panier', ['slug' => $slug]);
    }

    #[Route('/b/{slug}/panier/retirer/{id}', name: 'shop_panier_retirer', methods: ['POST'])]
    public function retirer(string $slug, int $id, Request $request, BoutiqueRepository $boutiques): Response
    {
        $this->trouverBoutique($slug, $boutiques);
        if (!$this->isCsrfTokenValid('panier', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $session = $request->getSession();
        $panier = $session->get($this->cle($slug), []);
        unset($panier[$id]);
        $session->set($this->cle($slug), $panier);
        return $this->redirectToRoute('shop_panier', ['slug' => $slug]);
    }

    #[Route('/b/{slug}/commander', name: 'shop_commander', methods: ['GET', 'POST'])]
    public function commander(string $slug, Request $request, BoutiqueRepository $boutiques, ProduitRepository $produits, EntityManagerInterface $em): Response
    {
        $b = $this->trouverBoutique($slug, $boutiques);
        $session = $request->getSession();
        [$lignes, $sousTotal] = $this->lignes($session->get($this->cle($slug), []), $produits, $b);
        if (!$lignes) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('shop_catalogue', ['slug' => $slug]);
        }
        $frais = $b->getFraisLivraison() ?? 0;
        $erreurs = [];
        $data = ['nom' => '', 'telephone' => '', 'adresse' => ''];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('commande', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException();
            }
            $data = [
                'nom' => trim($request->request->getString('nom')),
                'telephone' => trim($request->request->getString('telephone')),
                'adresse' => trim($request->request->getString('adresse')),
            ];
            if (mb_strlen($data['nom']) < 2) {
                $erreurs[] = 'Indiquez votre nom.';
            }
            if (!preg_match('/^[0-9+\s]{8,20}$/', $data['telephone'])) {
                $erreurs[] = 'Numéro de téléphone invalide.';
            }
            if (mb_strlen($data['adresse']) < 5) {
                $erreurs[] = 'Indiquez votre quartier / adresse de livraison.';
            }
            foreach ($lignes as $l) {
                if ($l['qte'] > $l['produit']->getStock()) {
                    $erreurs[] = 'Stock insuffisant pour ' . $l['produit']->getNom() . '.';
                }
            }

            $mode = ($request->request->getString('paiement') === 'momo' && $b->getMomoInfo()) ? 'momo' : 'livraison';

            if (!$erreurs) {
                $c = (new Commande())
                    ->setNomClient($data['nom'])
                    ->setTelephone($data['telephone'])
                    ->setAdresse($data['adresse'])
                    ->setBoutique($b)
                    ->setModePaiement($mode)
                    ->setTotal($sousTotal + $frais);
                foreach ($lignes as $l) {
                    $c->addLigne(
                        (new LigneCommande())
                            ->setProduit($l['produit'])
                            ->setQuantite($l['qte'])
                            ->setPrixUnitaire($l['produit']->getPrix())
                    );
                    $l['produit']->setStock($l['produit']->getStock() - $l['qte']);
                }
                $em->persist($c);
                $em->flush();

                $session->remove($this->cle($slug));
                $session->set('derniere_commande', $c->getId());
                return $this->redirectToRoute('shop_merci', ['slug' => $slug, 'id' => $c->getId()]);
            }
        }

        return $this->render('shop/commander.html.twig', [
            'boutique' => $b,
            'lignes' => $lignes,
            'sousTotal' => $sousTotal,
            'frais' => $frais,
            'total' => $sousTotal + $frais,
            'erreurs' => $erreurs,
            'data' => $data,
        ]);
    }

    #[Route('/b/{slug}/merci/{id}', name: 'shop_merci')]
    public function merci(string $slug, int $id, Request $request, BoutiqueRepository $boutiques, CommandeRepository $commandes): Response
    {
        $b = $this->trouverBoutique($slug, $boutiques);
        $c = $commandes->find($id);
        if (!$c || $c->getBoutique()?->getId() !== $b->getId() || $request->getSession()->get('derniere_commande') !== $id) {
            throw $this->createNotFoundException();
        }

        $msg = 'Bonjour, je souhaite confirmer ma commande n°' . $c->getId() . " :\n";
        foreach ($c->getLignes() as $l) {
            $msg .= '- ' . $l->getQuantite() . ' x ' . $l->getProduit()->getNom()
                . ' : ' . $this->fcfa($l->getPrixUnitaire() * $l->getQuantite()) . "\n";
        }
        $msg .= "\nTotal (livraison incluse) : " . $this->fcfa($c->getTotal())
            . "\n\nNom : " . $c->getNomClient()
            . "\nTéléphone : " . $c->getTelephone()
            . "\nAdresse : " . $c->getAdresse()
            . "\nPaiement : " . ($c->getModePaiement() === 'momo' ? 'Mobile Money (je vous envoie la preuve du transfert)' : 'à la livraison');

        $numero = preg_replace('/\D/', '', (string) $b->getNumeroWhatsapp());
        $lien = 'https://wa.me/' . $numero . '?text=' . rawurlencode($msg);

        return $this->render('shop/merci.html.twig', [
            'boutique' => $b,
            'commande' => $c,
            'whatsapp_url' => $lien,
        ]);
    }

    private function trouverBoutique(string $slug, BoutiqueRepository $boutiques): Boutique
    {
        return $boutiques->findOneBy(['slug' => $slug]) ?? throw $this->createNotFoundException('Boutique introuvable.');
    }

    private function cle(string $slug): string
    {
        return 'panier_' . $slug;
    }

    private function lignes(array $panier, ProduitRepository $produits, Boutique $b): array
    {
        $lignes = [];
        $total = 0;
        foreach ($panier as $id => $qte) {
            $p = $produits->find($id);
            if (!$p || $p->getBoutique()?->getId() !== $b->getId() || !$p->isActif()) {
                continue;
            }
            $sous = $p->getPrix() * $qte;
            $total += $sous;
            $lignes[] = ['produit' => $p, 'qte' => $qte, 'sous' => $sous];
        }
        return [$lignes, $total];
    }

    private function fcfa(int $n): string
    {
        return number_format($n, 0, ',', ' ') . ' FCFA';
    }
}
