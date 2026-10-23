<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/commande')]
class CommandeController extends AbstractAdminController
{
    public const STATUTS = [
        'en_attente' => 'En attente',
        'payee' => 'Payée',
        'livree' => 'Livrée',
        'annulee' => 'Annulée',
    ];

    #[Route('', name: 'app_commande_index', methods: ['GET'])]
    public function index(Request $request, CommandeRepository $repo): Response
    {
        $b = $this->maBoutique();
        $filtre = $request->query->getString('statut');
        $criteres = ['boutique' => $b];
        if (isset(self::STATUTS[$filtre])) {
            $criteres['statut'] = $filtre;
        }
        return $this->render('commande/index.html.twig', [
            'commandes' => $repo->findBy($criteres, ['id' => 'DESC']),
            'statuts' => self::STATUTS,
            'filtre' => $filtre,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Commande $commande): Response
    {
        $this->verifier($commande);
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
            'statuts' => self::STATUTS,
        ]);
    }

    #[Route('/{id}/statut', name: 'app_commande_statut', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function statut(Request $request, Commande $commande, EntityManagerInterface $em): Response
    {
        $this->verifier($commande);
        if (!$this->isCsrfTokenValid('statut' . $commande->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $nouveau = $request->getPayload()->getString('statut');
        if (!isset(self::STATUTS[$nouveau])) {
            $this->addFlash('error', 'Statut invalide.');
        } elseif ($commande->getStatut() === 'annulee') {
            $this->addFlash('error', 'Une commande annulée ne peut plus être modifiée.');
        } else {
            if ($nouveau === 'annulee') {
                foreach ($commande->getLignes() as $ligne) {
                    $p = $ligne->getProduit();
                    $p->setStock($p->getStock() + $ligne->getQuantite());
                }
            }
            $commande->setStatut($nouveau);
            $em->flush();
            $this->addFlash('success', 'Statut mis à jour : ' . self::STATUTS[$nouveau] . ($nouveau === 'annulee' ? ' (stock remis à jour).' : '.'));
        }

        return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
    }

    private function verifier(Commande $commande): void
    {
        if ($commande->getBoutique()?->getId() !== $this->maBoutique()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
