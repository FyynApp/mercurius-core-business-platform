<?php

namespace App\Shared\Domain\Command\Devhelper;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[AsCommand(name: 'app:devhelper:create-demo-entities')]
class CreateDemoEntities
    extends Command
{
    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $userPasswordHasher
    )
    {
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        parent::__construct();
    }

    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $email = 'j.doe@example.com';
        $password = 'test123';
        $user = $this->entityManager->getRepository(User::class)
                                    ->findOneBy(['email' => $email]);

        if (is_null($user)) {
            $user = new User();
            $user->setEmail($email);
            $user->setIsVerified(true);
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $password
                )
            );

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $presentationpage = new Presentationpage($user);
        $presentationpage->setType(PresentationpageType::Page);
        $presentationpage->setTitle('My first presentation-page!');

        $this->entityManager->persist($presentationpage);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
