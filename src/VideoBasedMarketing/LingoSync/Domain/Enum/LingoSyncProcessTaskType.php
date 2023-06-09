<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Enum;


enum LingoSyncProcessTaskType: string
{
    case GenerateAudioTranscription = 'generateOriginalLanguageTranscription';
    case WaitForTranslation = 'waitForTranslation';
    case CreateAudioSnippets = 'createAudioSnippets';
    case ConcatenateAudioSnippets = 'concatenateAudioSnippets';
    case GenerateTranslatedVideo = 'generateTranslatedVideo';
}
