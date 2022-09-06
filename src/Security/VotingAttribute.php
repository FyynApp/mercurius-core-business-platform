<?php

namespace App\Security;

enum VotingAttribute: string
{
    case View = 'view';
    case Edit = 'edit';
    case Delete = 'delete';
}
