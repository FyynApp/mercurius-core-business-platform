<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\Command;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\LingoSyncInfrastructureService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:lingosync:compactize-webvtt',
    description: '',
    aliases: ['compactize-webvtt']
)]
class CompactizeWebVtt
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
            'webVttFilePath',
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
        $output->writeln(
            $this->textToSpeechService::compactizeWebVtt(
                file_get_contents($webVttFilePath)
            )
        );

        return 0;
    }
}
