<?php

namespace App\VideoBasedMarketing\Presentationpages\Domain\Enum;

enum PresentationpageElementVariant: string
{
    case Divider = 'divider';
    case Headline = 'headline';
    case Paragraph = 'paragraph';
    case MercuriusVideo = 'mercuriusVideo';
    case ImageUrl = 'imageUrl';
    case CalendlyEmbed = 'calendlyEmbed';
}
