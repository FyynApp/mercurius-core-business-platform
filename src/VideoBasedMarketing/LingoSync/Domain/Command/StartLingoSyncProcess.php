<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Command;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:lingosync:start-process',
    description: '',
    aliases: ['start-lingosync-process']
)]
class StartLingoSyncProcess
    extends Command
{
    private readonly LingoSyncDomainService $lingoSyncDomainService;
    private readonly EntityManagerInterface $entityManager;

    public function __construct(
        LingoSyncDomainService $lingoSyncDomainService,
        EntityManagerInterface $entityManager
    )
    {
        $this->lingoSyncDomainService = $lingoSyncDomainService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    public function configure(): void
    {

        $this->addArgument(
            'videoId',
            InputArgument::REQUIRED
        );

        $this->addArgument(
            'originalLanguage',
            InputArgument::REQUIRED,
            "BCP47 language code of the video's original audio language, e.g. "
            . Bcp47LanguageCode::DeDe->value . ' or ' . Bcp47LanguageCode::EnUs->value
        );

        $this->addArgument(
            'targetLanguage',
            InputArgument::REQUIRED,
            "BCP47 language code of the target audio language, e.g. "
            . Bcp47LanguageCode::DeDe->value . ' or ' . Bcp47LanguageCode::EnUs->value
        );

        $this->addArgument(
            'originalGender',
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
        $videoId = $input->getArgument('videoId');
        $originalLanguage = Bcp47LanguageCode::from($input->getArgument('originalLanguage'));
        $targetLanguage = Bcp47LanguageCode::from($input->getArgument('targetLanguage'));
        $originalGender = Gender::from($input->getArgument('originalGender'));

        $video = $this->entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            $output->writeln("Could not find video with id '$videoId'.");
            return Command::FAILURE;
        }

        if ($originalLanguage === $targetLanguage) {
            $output->writeln('Languages must be different.');
            return Command::INVALID;
        }

        $lingoSyncProcess = $this
            ->lingoSyncDomainService
            ->startProcess(
                $video,
                $originalLanguage,
                $originalGender,
                [$targetLanguage]
            );

        $output->writeln("Started.");
        $output->writeln("LingoSyncProcess id: {$lingoSyncProcess->getId()}. ");
        $output->writeln("AudioTranscription id: {$lingoSyncProcess->getAudioTranscription()->getId()}. ");

        return 0;
    }
}
