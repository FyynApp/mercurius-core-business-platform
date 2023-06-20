<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Command;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:videobasedmarketing:audiotranscription:create',
    description: '',
    aliases: ['create-audio-transcription']
)]
class CreateAudioTranscriptionCommand
    extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
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
            'originalLanguageCode',
            InputArgument::REQUIRED,
            'BCP47 language code, e.g. '
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
        $videoId = $input->getArgument('videoId');
        $originalLanguageCode = Bcp47LanguageCode::from($input->getArgument('originalLanguageCode'));

        $video = $this->entityManager->find(
            Video::class,
            $videoId
        );

        if (is_null($video)) {
            $output->writeln(
                "Video with id '$videoId' not found."
            );
            return Command::FAILURE;
        }

        $audioTranscription = new AudioTranscription(
            $video,
            $originalLanguageCode
        );

        $this->entityManager->persist($audioTranscription);
        $this->entityManager->flush();

        $output->writeln("Audio transcription with id '{$audioTranscription->getId()}' created.");

        return Command::SUCCESS;
    }
}
