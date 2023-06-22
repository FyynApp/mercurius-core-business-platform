<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\Command;

use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\LingoSyncInfrastructureService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:lingosync:get-audio-file-length',
    description: '',
    aliases: ['get-audio-file-length']
)]
class GetAudioFileLength
    extends Command
{
    private readonly LingoSyncInfrastructureService $lingoSyncInfrastructureService;

    public function __construct(
        LingoSyncInfrastructureService $lingoSyncInfrastructureService
    )
    {
        $this->lingoSyncInfrastructureService = $lingoSyncInfrastructureService;
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'audioFilePath',
            InputArgument::REQUIRED
        );

        parent::configure();
    }

    /**
     * @throws Exception
     */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $audioFilePath = $input->getArgument('audioFilePath');

        $length = $this->lingoSyncInfrastructureService->getAudioFileDurationInMilliseconds(
            $audioFilePath
        );

        $output->writeln("Length is $length ms.");

        return 0;
    }
}
