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
    name: 'app:videobasedmarketing:lingosync:audio-file-is-usable',
    description: '',
    aliases: ['audio-file-is-usable']
)]
class AudioFileIsUsable
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

        $isUsable = $this->textToSpeechService::audioFileIsUsable(
            $sourceAudioFilePath
        );

        $output->writeln('Done. Result: ' . ($isUsable ? 'true' : 'false') . '.');

        return 0;
    }
}
