<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Enum;


enum LingoSyncProcessTaskType: string
{
    case GenerateOriginalLanguageTranscription = 'generateOriginalLanguageTranscription';
    case GenerateTargetLanguageTranscription = 'generateTargetLanguageTranscription';
    case GenerateAudioSnippets = 'generateAudioSnippets';
    case GenerateConcatenatedAudio = 'generateConcatenatedAudio';
    case GenerateTranslatedVideo = 'generateTranslatedVideo';
}
