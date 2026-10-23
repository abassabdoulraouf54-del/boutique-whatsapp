<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:mot-de-passe', description: 'Définit le mot de passe d\'un compte existant')]
class MotDePasseCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email du compte')
            ->addArgument('motdepasse', InputArgument::OPTIONAL, 'Nouveau mot de passe (sinon il est demandé)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error("Aucun compte avec l'email « $email ».");
            $io->listing(array_map(fn (User $u) => $u->getEmail(), $this->em->getRepository(User::class)->findAll()));
            return Command::FAILURE;
        }

        $mdp = $input->getArgument('motdepasse') ?: $io->askHidden('Nouveau mot de passe (8 caractères minimum)');
        if (!$mdp || strlen($mdp) < 8) {
            $io->error('Mot de passe trop court (8 caractères minimum).');
            return Command::FAILURE;
        }

        $user->setPassword($this->hasher->hashPassword($user, $mdp));
        $this->em->flush();
        $io->success("Mot de passe mis à jour pour $email.");
        return Command::SUCCESS;
    }
}
