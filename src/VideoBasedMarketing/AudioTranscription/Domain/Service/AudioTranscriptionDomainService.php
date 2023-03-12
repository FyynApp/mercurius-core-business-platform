<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Service;


use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Enum\AudioTranscriptionBcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CreateHappyScribeTranscriptionCommandMessage;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class AudioTranscriptionDomainService
{
    public function __construct(
        private MessageBusInterface    $messageBus,
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * @throws Exception
     */
    public function createAudioTranscription(
        Video $video,
        AudioTranscriptionBcp47LanguageCode $audioTranscriptionBcp47LanguageCode
    ): AudioTranscription
    {
        $audioTranscription = new AudioTranscription(
            $video,
            $audioTranscriptionBcp47LanguageCode
        );

        $this->entityManager->persist($audioTranscription);
        $this->entityManager->flush();

        return $audioTranscription;
    }

    public function startProcessingAudioTranscription(
        AudioTranscription $audioTranscription
    ): void
    {
        $this->messageBus->dispatch(
            new CreateHappyScribeTranscriptionCommandMessage(
                $audioTranscription
            )
        );
    }
}
