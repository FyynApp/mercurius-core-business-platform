<?php

namespace App\Entity\Feature\Presentationpages;

enum PresentationpageElementVariant: string
{
    case Headline = 'headline';
    case Paragraph = 'paragraph';
    case MercuriusVideo = 'mercuriusVideo';
    case ImageUrl = 'imageUrl';
    case CalendlyEmbed = 'calendlyEmbed';
}
