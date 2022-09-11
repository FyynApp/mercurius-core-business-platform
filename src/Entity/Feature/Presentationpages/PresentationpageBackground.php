<?php

namespace App\Entity\Feature\Presentationpages;

use InvalidArgumentException;

enum PresentationpageBackground: string
{
    case BgColor = 'bg-color';
    case ImageOfficeOne = 'image-office-one';
    case ImageLibraryOne = 'image-library-one';
    case ImageStoreOne = 'image-store-one';

    public function toAssetName(): string
    {
        if ($this === self::BgColor) {
            throw new InvalidArgumentException('No asset for bgColor.');
        }

        return "background-{$this->value}.jpg";
    }

    public function toPreviewAssetName(): string
    {
        if ($this === self::BgColor) {
            throw new InvalidArgumentException('No asset for bgColor.');
        }

        return "background-{$this->value}-preview.jpg";
    }
}
