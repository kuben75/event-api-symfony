<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:assign-role',
    description: 'Assigns a new role to a user.',
)]
class UserAssignRoleCommand extends Command
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the user.')
            ->addArgument('role', InputArgument::REQUIRED, 'The role to assign (e.g., ROLE_ORGANIZER).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email "%s" not found.', $email));
            return Command::FAILURE;
        }

        $user->setRoles([$role]);
        $this->entityManager->flush();

        $io->success(sprintf('Role "%s" has been successfully assigned to user "%s".', $role, $email));

        return Command::SUCCESS;
    }
}
