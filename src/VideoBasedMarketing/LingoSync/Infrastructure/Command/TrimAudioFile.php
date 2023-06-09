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
    name: 'app:videobasedmarketing:lingosync:trim-audio-file',
    description: '',
    aliases: ['trim-audio-file']
)]
class TrimAudioFile
    extends Command
{
    private readonly LingoSyncInfrastructureService $textToSpeechService;

    public function __construct(
        LingoSyncInfrastructureService $textToSpeechService
    )
    {
        $this->textToSpeechService = $textToSpeechService;
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'sourceAudioFilePath',
            InputArgument::REQUIRED
        );

        $this->addArgument(
            'targetAudioFilePath',
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
        $sourceAudioFilePath = $input->getArgument('sourceAudioFilePath');
        $targetAudioFilePath = $input->getArgument('targetAudioFilePath');

        $this->textToSpeechService::trimAudioFile(
            $sourceAudioFilePath,
            $targetAudioFilePath
        );

        $output->writeln('Done.');

        return 0;
    }
}
