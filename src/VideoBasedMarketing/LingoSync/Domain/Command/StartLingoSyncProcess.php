<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Command;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
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

    public function configure()
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

        $video = $this->entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            $output->writeln("Could not find video with id '$videoId'.");
            return 1;
        }

        $lingoSyncProcess = $this->lingoSyncDomainService->startLingoSyncProcess(
            $video,
            $originalLanguage,
            []
        );

        $output->writeln("LingoSync process id: {$lingoSyncProcess->getId()}");

        return 0;
    }
}
