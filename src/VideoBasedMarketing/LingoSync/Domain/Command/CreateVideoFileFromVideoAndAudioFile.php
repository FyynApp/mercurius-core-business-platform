<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Command;

use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\LingoSyncInfrastructureService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:videobasedmarketing:lingosync:create-video-file-from-video-and-audio-file',
    description: '',
    aliases: ['lingosync:create-video-file-from-video-and-audio-file']
)]
class CreateVideoFileFromVideoAndAudioFile
    extends Command
{
    private readonly LingoSyncInfrastructureService $lingoSyncInfrastructureService;
    private readonly EntityManagerInterface $entityManager;

    public function __construct(
        LingoSyncInfrastructureService $lingoSyncInfrastructureService,
        EntityManagerInterface         $entityManager
    )
    {
        $this->lingoSyncInfrastructureService = $lingoSyncInfrastructureService;
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
            'audioFilePath',
            InputArgument::REQUIRED
        );

        $this->addOption(
            'merge-audio',
            'm',
            InputOption::VALUE_NONE
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
        $audioFilePath = $input->getArgument('audioFilePath');
        $mergeAudio = (bool)$input->getOption('merge-audio');

        $video = $this->entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            $output->writeln("Could not find video with id '$videoId'.");
            return Command::FAILURE;
        }

        $targetFilePath = $this
            ->lingoSyncInfrastructureService
            ->createVideoFileFromVideoAndAudioFile(
                $video,
                $audioFilePath,
                $mergeAudio
            );

        $output->writeln("Created video file at '$targetFilePath'.");

        return 0;
    }
}
