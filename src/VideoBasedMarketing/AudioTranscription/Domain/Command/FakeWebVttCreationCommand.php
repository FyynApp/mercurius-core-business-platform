<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Command;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Domain\SymfonyEvent\WebVttBecameAvailableSymfonyEvent;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:videobasedmarketing:audiotranscription:fake-webvtt-creation',
    description: '',
    aliases: ['fake-webvtt-creation']
)]
class FakeWebVttCreationCommand
    extends Command
{
    private EntityManagerInterface $entityManager;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface   $entityManager,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'audioTranscriptionId',
            InputArgument::REQUIRED
        );

        $this->addArgument(
            'languageCode',
            InputArgument::REQUIRED,
            'BCP47 language code, e.g. '
            . Bcp47LanguageCode::DeDe->value . ' or ' . Bcp47LanguageCode::EnUs->value
        );

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
        $audioTranscriptionId = $input->getArgument('audioTranscriptionId');
        $languageCode = Bcp47LanguageCode::from($input->getArgument('languageCode'));
        $webVttFilePath = $input->getArgument('webVttFilePath');

        $audioTranscription = $this->entityManager->find(
            AudioTranscription::class,
            $audioTranscriptionId
        );

        if (is_null($audioTranscription)) {
            $output->writeln(
                "AudioTranscription with id '$audioTranscriptionId' not found."
            );
            return Command::FAILURE;
        }

        $vtt = new AudioTranscriptionWebVtt(
            $audioTranscription,
            $languageCode,
            file_get_contents($webVttFilePath)
        );

        $this->entityManager->persist($vtt);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new WebVttBecameAvailableSymfonyEvent($vtt)
        );

        return Command::SUCCESS;
    }
}
