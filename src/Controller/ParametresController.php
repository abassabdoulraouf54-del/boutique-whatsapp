<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Regex;

class ParametresController extends AbstractAdminController
{
    #[Route('/admin/parametres', name: 'admin_parametres', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $b = $this->maBoutique();

        $form = $this->createFormBuilder($b)
            ->add('nom', TextType::class, [
                'label' => 'Nom de la boutique',
                'constraints' => [new NotBlank(message: 'Le nom est obligatoire.'), new Length(max: 80)],
            ])
            ->add('slogan', TextType::class, [
                'label' => 'Slogan (phrase d\'accroche)',
                'required' => false,
                'constraints' => [new Length(max: 120)],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse de la boutique',
                'required' => false,
                'constraints' => [new Length(max: 200)],
            ])
            ->add('numeroWhatsapp', TextType::class, [
                'label' => 'Numéro WhatsApp (avec indicatif, ex : 22997123456)',
                'constraints' => [new Regex('/^[0-9+\s]{8,20}$/', message: 'Numéro invalide : chiffres uniquement, avec l\'indicatif.')],
            ])
            ->add('fraisLivraison', IntegerType::class, [
                'label' => 'Frais de livraison (FCFA)',
                'required' => false,
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('momoInfo', TextType::class, [
                'label' => 'Paiement Mobile Money : opérateur, numéro et nom du titulaire (vide = désactivé)',
                'required' => false,
                'attr' => ['placeholder' => 'MTN MoMo 97 00 00 00 - Aïcha K.'],
                'constraints' => [new Length(max: 200)],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Numéro pour les appels (avec indicatif, ex : 2290152400299)',
                'required' => false,
                'constraints' => [new Regex('/^[0-9+\s]{8,20}$/', message: 'Numéro invalide : chiffres uniquement, avec l\'indicatif.')],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo (JPG, PNG ou WebP)',
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
                'constraints' => [new File(
                    maxSize: '3M',
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    mimeTypesMessage: 'Envoyez une image JPG, PNG ou WebP.',
                    maxSizeMessage: 'Le logo est trop lourd (3 Mo maximum).',
                )],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $b->setNumeroWhatsapp(preg_replace('/\D/', '', (string) $b->getNumeroWhatsapp()));
            $b->setFraisLivraison($b->getFraisLivraison() ?? 0);
            $b->setTelephone($b->getTelephone() ? preg_replace('/\D/', '', $b->getTelephone()) : null);

            $fichier = $form->get('logoFile')->getData();
            if ($fichier) {
                $base = $slugger->slug(pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME))->lower();
                $nom = $base . '-' . bin2hex(random_bytes(4)) . '.' . ($fichier->guessExtension() ?: 'png');
                $fichier->move($this->getParameter('kernel.project_dir') . '/public/uploads/logos', $nom);

                $ancien = $b->getLogo();
                if ($ancien && str_starts_with($ancien, '/uploads/logos/')) {
                    $chemin = $this->getParameter('kernel.project_dir') . '/public' . $ancien;
                    if (is_file($chemin)) {
                        @unlink($chemin);
                    }
                }
                $b->setLogo('/uploads/logos/' . $nom);
            }

            $em->flush();
            $this->addFlash('success', 'Paramètres enregistrés.');
            return $this->redirectToRoute('admin_parametres');
        }

        return $this->render('admin/parametres.html.twig', ['form' => $form, 'boutique' => $b]);
    }
}
