<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Command;

use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Message\GenerateMissingVideoAssetsCommandMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:videobasedmarketing:recordings:infrastructure:dispatch-generate-missing-video-assets-command-messages',
    description: '',
    aliases: ['dispatch-generate-missing-video-assets']
)]
class DispatchGenerateMissingVideoAssetsCommandMessages
    extends Command
{
    private EntityManagerInterface $entityManager;

    private MessageBusInterface $messageBus;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageBusInterface    $messageBus
    )
    {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        parent::__construct();
    }

    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        /** @var ObjectRepository<Video> $repo */
        $repo = $this->entityManager->getRepository(Video::class);

        /** @var Video $video */
        foreach ($repo->findAll() as $video) {
            $this->messageBus->dispatch(
                new GenerateMissingVideoAssetsCommandMessage($video)
            );
        }

        return 0;
    }
}
