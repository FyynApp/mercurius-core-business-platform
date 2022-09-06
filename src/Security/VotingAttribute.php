<?php

namespace App\Security;

enum VotingAttribute: string
{
    case View = 'view';
    case Edit = 'edit';
    case Use = 'use';
    case Delete = 'delete';
}
