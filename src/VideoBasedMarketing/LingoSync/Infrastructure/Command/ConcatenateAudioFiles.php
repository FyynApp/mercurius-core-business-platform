<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\Command;

use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\TextToSpeechService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:lingosync:concatenate-audio-files',
    description: '',
    aliases: ['concatenate-audio-files']
)]
class ConcatenateAudioFiles
    extends Command
{
    private readonly TextToSpeechService $textToSpeechService;

    public function __construct(
        TextToSpeechService $textToSpeechService
    )
    {
        $this->textToSpeechService = $textToSpeechService;
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'webVttFilePath',
            InputArgument::REQUIRED
        );

        $this->addArgument(
            'sourceAudioFilesFolderPath',
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
        $webVttFilePath = $input->getArgument('webVttFilePath');
        $sourceAudioFilesFolderPath = $input->getArgument('sourceAudioFilesFolderPath');
        $targetAudioFilePath = $input->getArgument('targetAudioFilePath');

        $this->textToSpeechService->concatenateAudioFiles(
            $this->textToSpeechService::compactizeWebvtt(
                file_get_contents($webVttFilePath)
            ),
            $sourceAudioFilesFolderPath,
            $targetAudioFilePath
        );

        $output->writeln('Done.');

        return 0;
    }
}
