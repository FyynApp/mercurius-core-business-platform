<?php

namespace App\Entity\Feature\PresentationpageTemplates;

enum PresentationpageTemplateElementVariant: string
{
    case Headline = 'headline';
    case Paragraph = 'paragraph';
    case MercuriusVideo = 'mercuriusVideo';
    case ImageUrl = 'imageUrl';
    case CalendlyEmbed = 'calendlyEmbed';
}
