<?php

namespace App\Shared\Presentation\Enum;

enum FlashMessageLabel: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
    case Info = 'info';
}
