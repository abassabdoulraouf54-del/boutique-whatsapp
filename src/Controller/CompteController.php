<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CompteController extends AbstractAdminController
{
    #[Route('/admin/compte', name: 'admin_compte', methods: ['GET', 'POST'])]
    public function index(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createFormBuilder()
            ->add('actuel', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'attr' => ['autocomplete' => 'current-password'],
                'constraints' => [new NotBlank(message: 'Saisissez votre mot de passe actuel.')],
            ])
            ->add('nouveau', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Nouveau mot de passe (8 caractères minimum)',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Répétez le nouveau mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les deux mots de passe ne sont pas identiques.',
                'constraints' => [
                    new NotBlank(message: 'Saisissez un nouveau mot de passe.'),
                    new Length(min: 8, minMessage: '8 caractères minimum.', max: 100),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$hasher->isPasswordValid($user, (string) $form->get('actuel')->getData())) {
                $form->get('actuel')->addError(new FormError('Mot de passe actuel incorrect.'));
            } else {
                $user->setPassword($hasher->hashPassword($user, (string) $form->get('nouveau')->getData()));
                $em->flush();
                $this->addFlash('success', 'Mot de passe modifié. Utilisez-le à votre prochaine connexion.');
                return $this->redirectToRoute('admin_compte');
            }
        }

        return $this->render('admin/compte.html.twig', ['form' => $form]);
    }
}
