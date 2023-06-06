<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\Command;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\TextToSpeechService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:lingosync:create-audio-files-for-webvtt-cues',
    description: '',
    aliases: ['create-audio-files-for-webvtt-cues']
)]
class CreateAudioFilesForWebVttCues
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
            'languageCode',
            InputArgument::REQUIRED,
            "BCP47 language code, e.g. "
            . Bcp47LanguageCode::DeDe->value . ' or ' . Bcp47LanguageCode::EnUs->value
        );

        $this->addArgument(
            'gender',
            InputArgument::REQUIRED,
            "Gender, use "
            . Gender::Male->value . ' or ' . Gender::Female->value
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
        $languageCode = Bcp47LanguageCode::from($input->getArgument('languageCode'));
        $gender = Gender::from($input->getArgument('gender'));

        $folderPath = $this->textToSpeechService->createAudioFilesForWebVttCues(
            file_get_contents($webVttFilePath),
            $languageCode,
            $gender
        );

        $output->writeln("Done. Files are stored in $folderPath");

        return 0;
    }
}
