<?php

namespace App\Shared\Infrastructure\Command\Devhelper;

use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage\GenerateSuggestedSummaryCommandSymfonyMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;


#[AsCommand(
    name: 'app:shared:infrastrcuture:devhelper:dispatch-symfony-message',
    description: 'Dispatch a Symfony Messenger message',
    aliases: ['dispatch-symfony-message']
)]
class DispatchSymfonyMessageEntities
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
        $entity = $this
            ->entityManager
            ->find(
                AudioTranscriptionWebVtt::class,
                '1edc1a22-0b21-6506-b80d-016d57482aa3'
            );

        $this->messageBus->dispatch(
            new GenerateSuggestedSummaryCommandSymfonyMessage(
                $entity
            )
        );

        return 0;
    }
}
