<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\Command;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\LingoSyncInfrastructureService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:lingosync:translate-webvtt',
    description: '',
    aliases: ['translate-webvtt']
)]
class TranslateWebVtt
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
            'webVttFilePath',
            InputArgument::REQUIRED
        );

        $this->addArgument(
            'originalLanguageCode',
            InputArgument::REQUIRED,
            "Original BCP47 language code, e.g. "
            . Bcp47LanguageCode::DeDe->value . ' or ' . Bcp47LanguageCode::EnUs->value
        );

        $this->addArgument(
            'targetLanguageCode',
            InputArgument::REQUIRED,
            "Target BCP47 language code, e.g. "
            . Bcp47LanguageCode::DeDe->value . ' or ' . Bcp47LanguageCode::EnUs->value
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
        $originalLanguageCode = Bcp47LanguageCode::from($input->getArgument('originalLanguageCode'));
        $targetLanguageCode = Bcp47LanguageCode::from($input->getArgument('targetLanguageCode'));

        $translatedWebVtt = $this
            ->lingoSyncInfrastructureService
            ->translateWebVtt(
                file_get_contents($webVttFilePath),
                $originalLanguageCode,
                $targetLanguageCode
            );

        $output->writeln($translatedWebVtt);

        return 0;
    }
}
