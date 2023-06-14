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
    name: 'app:videobasedmarketing:lingosync:map-webvtt-timestamps',
    description: '',
    aliases: ['map-webvtt-timestamps']
)]
class MapWebVttTimestamps
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
            'webvttWithCorrectTimestampsFilePath',
            InputArgument::REQUIRED
        );

        $this->addArgument(
            'webvttWithCorrectTextsFilePath',
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
        $webvttWithCorrectTimestampsFilePath = $input->getArgument('webvttWithCorrectTimestampsFilePath');
        $webvttWithCorrectTextsFilePath = $input->getArgument('webvttWithCorrectTextsFilePath');

        $output->writeln(
            $this->textToSpeechService::mapWebVttTimestamps(
                file_get_contents($webvttWithCorrectTimestampsFilePath),
                file_get_contents($webvttWithCorrectTextsFilePath)
            )
        );

        return 0;
    }
}
