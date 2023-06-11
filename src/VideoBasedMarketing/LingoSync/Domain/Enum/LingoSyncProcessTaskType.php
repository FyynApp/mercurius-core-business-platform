<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Enum;


enum LingoSyncProcessTaskType: string
{
    case GenerateOriginalLanguageTranscription = 'generateOriginalLanguageTranscription';
    case WaitForTranslation = 'waitForTranslation';
    case CreateAudioSnippets = 'createAudioSnippets';
    case ConcatenateAudioSnippets = 'concatenateAudioSnippets';
    case GenerateTranslatedVideo = 'generateTranslatedVideo';
}
