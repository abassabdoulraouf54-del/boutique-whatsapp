<?php

namespace App\Command;

use App\Entity\Boutique;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(name: 'app:creer-boutique', description: 'Crée une boutique et le compte de son commerçant')]
class CreerBoutiqueCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private SluggerInterface $slugger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('nom', InputArgument::REQUIRED, 'Nom de la boutique')
            ->addArgument('whatsapp', InputArgument::REQUIRED, 'Numéro WhatsApp avec indicatif, ex : 22997000000')
            ->addArgument('email', InputArgument::REQUIRED, 'Email de connexion du commerçant');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $nom = trim((string) $input->getArgument('nom'));
        $numero = preg_replace('/\D/', '', (string) $input->getArgument('whatsapp'));
        $email = mb_strtolower(trim((string) $input->getArgument('email')));

        if ($nom === '' || strlen($numero) < 8 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Nom, numéro WhatsApp ou email invalide.');
            return Command::FAILURE;
        }
        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
            $io->error('Cet email existe déjà.');
            return Command::FAILURE;
        }

        $mdp = $io->askHidden('Mot de passe du commerçant (8 caractères minimum, 12 conseillés)');
        if (!$mdp || strlen($mdp) < 8) {
            $io->error('Mot de passe trop court.');
            return Command::FAILURE;
        }

        $base = $this->slugger->slug($nom)->lower()->toString() ?: 'boutique';
        $slug = $base;
        $i = 2;
        $repo = $this->em->getRepository(Boutique::class);
        while ($repo->findOneBy(['slug' => $slug])) {
            $slug = $base . '-' . $i++;
        }

        $boutique = (new Boutique())
            ->setNom($nom)
            ->setSlug($slug)
            ->setNumeroWhatsapp($numero)
            ->setCouleurPrincipale('#16a34a')
            ->setFraisLivraison(1000);

        $user = (new User())
            ->setEmail($email)
            ->setRoles(['ROLE_ADMIN'])
            ->setBoutique($boutique);
        $user->setPassword($this->hasher->hashPassword($user, $mdp));

        $this->em->persist($boutique);
        $this->em->persist($user);
        $this->em->flush();

        $io->success("Boutique créée. Lien client : /b/$slug  |  Connexion : /login avec $email");
        return Command::SUCCESS;
    }
}
