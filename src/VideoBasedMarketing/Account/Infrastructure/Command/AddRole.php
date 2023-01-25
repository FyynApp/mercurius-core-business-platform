<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Command;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:account:infrastructure:add-role',
    description: '',
    aliases: ['add-role']
)]
class AddRole
    extends Command
{
    private readonly EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    public function configure()
    {
        $this->addArgument('email', InputArgument::REQUIRED);
        $this->addArgument('role', InputArgument::REQUIRED);
        parent::configure();
    }

    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $emailArgument = $input->getArgument('email');

        /** @var ObjectRepository $repo */
        $repo = $this->entityManager->getRepository(User::class);

        /** @var null|User $user */
        $user = $repo->findOneBy(['email' => $emailArgument]);

        if (is_null($user)) {
            $output->writeln("Could not find user with email '$emailArgument'.");
            return 1;
        }

        $roleArgument = $input->getArgument('role');

        $role = Role::tryFrom($roleArgument);

        if (is_null($role)) {
            $output->writeln("Could not find role '$roleArgument'.");
            return 2;
        }

        $user->addRole($role);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln("Added role '$roleArgument' to user '{$user->getId()}' ($emailArgument).");

        return 0;
    }
}
