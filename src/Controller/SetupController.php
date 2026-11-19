<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SetupController extends AbstractController
{
    #[Route('/setup', name: 'app_setup')]
    public function setup(EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $email = 'abassabdoulraouf54@gmail.com';
        $password = 'Admin1234!';

        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return new Response('Compte deja existant : '.$email);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        return new Response('Compte cree ! Email : '.$email.' / Mot de passe : '.$password);
    }
}
