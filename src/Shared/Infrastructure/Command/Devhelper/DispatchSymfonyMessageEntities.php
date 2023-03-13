<?php

namespace App\Shared\Infrastructure\Command\Devhelper;

use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CheckHappyScribeTranscriptionCommandMessage;
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
        $happyScribeTranscription = $this
            ->entityManager
            ->find(
                HappyScribeTranscription::class,
                '1edc1956-c8f6-6c6a-abc1-ff8ab862640d'
            );

        $this->messageBus->dispatch(
            new CheckHappyScribeTranscriptionCommandMessage(
                $happyScribeTranscription
            )
        );

        return 0;
    }
}
