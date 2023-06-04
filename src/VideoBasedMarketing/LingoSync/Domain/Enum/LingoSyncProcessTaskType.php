<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Enum;


enum LingoSyncProcessTaskType: string
{
    case GenerateAudioTranscription = 'generateOriginalLanguageTranscription';
    case WaitForTranslation = 'waitForTranslation';
    case CreateAudioSnippet = 'createAudioSnippet';
    case ConcatenateAudioSnippets = 'concatenateAudioSnippets';
    case GenerateTranslatedVideo = 'generateTranslatedVideo';
}
