<?php

namespace App\Controller;

use App\Entity\Boutique;
use App\Entity\Produit;
use App\Entity\User;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/produit')]
class ProduitController extends AbstractController
{
    #[Route('', name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $repo): Response
    {
        $b = $this->maBoutique();
        return $this->render('produit/index.html.twig', [
            'produits' => $repo->findBy(['boutique' => $b], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $b = $this->maBoutique();
        if ($em->getRepository(\App\Entity\Categorie::class)->count(['boutique' => $b]) === 0) {
            $this->addFlash('error', 'Créez d\'abord une catégorie pour pouvoir ajouter des produits.');
            return $this->redirectToRoute('app_categorie_new');
        }
        $produit = (new Produit())->setBoutique($b);
        $form = $this->createForm(ProduitType::class, $produit, ['boutique' => $b]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($chemin = $this->enregistrerImage($form->get('imageFile')->getData(), $slugger)) {
                $produit->setImage($chemin);
            }
            $em->persist($produit);
            $em->flush();
            $this->addFlash('success', 'Produit créé.');
            return $this->redirectToRoute('app_produit_index');
        }

        return $this->render('produit/new.html.twig', ['produit' => $produit, 'form' => $form]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Produit $produit): Response
    {
        $this->verifier($produit);
        return $this->render('produit/show.html.twig', ['produit' => $produit]);
    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $b = $this->verifier($produit);
        $form = $this->createForm(ProduitType::class, $produit, ['boutique' => $b]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($chemin = $this->enregistrerImage($form->get('imageFile')->getData(), $slugger)) {
                $this->supprimerImage($produit->getImage());
                $produit->setImage($chemin);
            }
            $em->flush();
            $this->addFlash('success', 'Produit modifié.');
            return $this->redirectToRoute('app_produit_index');
        }

        return $this->render('produit/edit.html.twig', ['produit' => $produit, 'form' => $form]);
    }

    #[Route('/{id}', name: 'app_produit_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        $this->verifier($produit);
        if ($this->isCsrfTokenValid('delete' . $produit->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $image = $produit->getImage();
                $em->remove($produit);
                $em->flush();
                $this->supprimerImage($image);
                $this->addFlash('success', 'Produit supprimé.');
            } catch (ForeignKeyConstraintViolationException) {
                $this->addFlash('error', 'Ce produit figure dans des commandes. Décochez « Visible dans la boutique » à la place.');
            }
        }
        return $this->redirectToRoute('app_produit_index');
    }

    private function maBoutique(): Boutique
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getBoutique()) {
            throw $this->createAccessDeniedException('Aucune boutique associée à ce compte.');
        }
        return $user->getBoutique();
    }

    private function verifier(Produit $produit): Boutique
    {
        $b = $this->maBoutique();
        if ($produit->getBoutique()?->getId() !== $b->getId()) {
            throw $this->createAccessDeniedException();
        }
        return $b;
    }

    private function enregistrerImage(?UploadedFile $fichier, SluggerInterface $slugger): ?string
    {
        if (!$fichier) {
            return null;
        }
        $base = $slugger->slug(pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME))->lower();
        $nom = $base . '-' . bin2hex(random_bytes(4)) . '.' . ($fichier->guessExtension() ?: 'jpg');
        $fichier->move($this->getParameter('kernel.project_dir') . '/public/uploads/produits', $nom);
        return '/uploads/produits/' . $nom;
    }

    private function supprimerImage(?string $chemin): void
    {
        if ($chemin && str_starts_with($chemin, '/uploads/produits/')) {
            $fichier = $this->getParameter('kernel.project_dir') . '/public' . $chemin;
            if (is_file($fichier)) {
                @unlink($fichier);
            }
        }
    }
}
