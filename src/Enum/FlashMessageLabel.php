<?php

namespace App\Enum;

enum FlashMessageLabel: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
    case Info = 'info';
}
