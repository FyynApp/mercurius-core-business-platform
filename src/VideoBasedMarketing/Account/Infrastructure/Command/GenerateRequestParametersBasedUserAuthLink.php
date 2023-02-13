<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Command;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:account:infrastructure:create-auth-link',
    description: '',
    aliases: ['create-auth-link']
)]
class GenerateRequestParametersBasedUserAuthLink
    extends Command
{
    private readonly RequestParametersBasedUserAuthService $authService;
    private readonly EntityManagerInterface $entityManager;

    public function __construct(
        RequestParametersBasedUserAuthService $authService,
        EntityManagerInterface                $entityManager
    )
    {
        $this->authService = $authService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    public function configure()
    {

        $this->addArgument(
            'email',
            InputArgument::REQUIRED
        );

        $this->addArgument(
            'route_name',
            InputArgument::OPTIONAL,
            '',
            'shared.presentation.contentpages.homepage'
        );

        parent::configure();
    }

    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $emailArgument = $input->getArgument('email');
        $routeNameArgument = $input->getArgument('route_name');

        /** @var ObjectRepository $repo */
        $repo = $this->entityManager->getRepository(User::class);

        /** @var null|User $user */
        $user = $repo->findOneBy(['email' => $emailArgument]);

        if (is_null($user)) {
            $output->writeln("Could not find user with email '$emailArgument'.");
            return 1;
        }

        $url = $this->authService->createUrl(
            $user,
            $routeNameArgument,
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $output->writeln($url);

        return 0;
    }
}
