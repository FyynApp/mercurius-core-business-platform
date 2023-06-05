<?php

namespace App\VideoBasedMarketing\LingoSync\Infrastructure\Command;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\VideoBasedMarketing\LingoSync\Infrastructure\ApiClient\GoogleCloudTextToSpeechApiClient;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:lingosync:create-audio-file-from-text',
    description: '',
    aliases: ['create-audio-file']
)]
class CreateAudioFileFromText
    extends Command
{
    private readonly GoogleCloudTextToSpeechApiClient $googleCloudTextToSpeechApiClient;

    public function __construct(
        GoogleCloudTextToSpeechApiClient $googleCloudTextToSpeechApiClient
    )
    {
        $this->googleCloudTextToSpeechApiClient = $googleCloudTextToSpeechApiClient;
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'text',
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

        $this->addArgument(
            'speakingRate',
            InputArgument::REQUIRED
        );

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
        $text = $input->getArgument('text');
        $languageCode = Bcp47LanguageCode::from($input->getArgument('languageCode'));
        $gender = Gender::from($input->getArgument('gender'));
        $speakingRate = (float)$input->getArgument('speakingRate');
        $audioFilePath = $input->getArgument('audioFilePath');

        $this->googleCloudTextToSpeechApiClient->createAudioFileFromText(
            $text,
            $languageCode,
            $gender,
            $speakingRate,
            $audioFilePath
        );

        $output->writeln('Done.');

        return 0;
    }
}
