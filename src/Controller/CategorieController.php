<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categorie')]
class CategorieController extends AbstractAdminController
{
    #[Route('', name: 'app_categorie_index', methods: ['GET'])]
    public function index(CategorieRepository $repo): Response
    {
        return $this->render('categorie/index.html.twig', [
            'categories' => $repo->findBy(['boutique' => $this->maBoutique()], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_categorie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $categorie = (new Categorie())->setBoutique($this->maBoutique());
        $form = $this->formulaire($categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($categorie);
            $em->flush();
            $this->addFlash('success', 'Catégorie créée.');
            return $this->redirectToRoute('app_categorie_index');
        }
        return $this->render('categorie/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_categorie_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Categorie $categorie, EntityManagerInterface $em): Response
    {
        $this->verifier($categorie);
        $form = $this->formulaire($categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie modifiée.');
            return $this->redirectToRoute('app_categorie_index');
        }
        return $this->render('categorie/edit.html.twig', ['categorie' => $categorie, 'form' => $form]);
    }

    #[Route('/{id}', name: 'app_categorie_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Categorie $categorie, EntityManagerInterface $em): Response
    {
        $this->verifier($categorie);
        if ($this->isCsrfTokenValid('delete' . $categorie->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $em->remove($categorie);
                $em->flush();
                $this->addFlash('success', 'Catégorie supprimée.');
            } catch (ForeignKeyConstraintViolationException) {
                $this->addFlash('error', 'Cette catégorie contient encore des produits. Déplacez-les d\'abord.');
            }
        }
        return $this->redirectToRoute('app_categorie_index');
    }

    private function formulaire(Categorie $categorie)
    {
        return $this->createFormBuilder($categorie)
            ->add('nom', TextType::class, ['label' => 'Nom de la catégorie'])
            ->getForm();
    }

    private function verifier(Categorie $categorie): void
    {
        if ($categorie->getBoutique()?->getId() !== $this->maBoutique()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
