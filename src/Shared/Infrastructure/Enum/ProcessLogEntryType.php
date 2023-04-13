<?php

namespace App\Shared\Infrastructure\Enum;

enum ProcessLogEntryType: string
{
    case GenerateMissingVideoAssets = 'GenerateMissingVideoAssets';
    case GenerateVideoAssetPosterStillWebp = 'GenerateVideoAssetPosterStillWebp';
    case GenerateVideoAssetPosterAnimatedWebp = 'GenerateVideoAssetPosterAnimatedWebp';
    case GenerateVideoAssetFullMp4 = 'GenerateVideoAssetFullMp4';
}
