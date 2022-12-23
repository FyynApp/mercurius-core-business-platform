<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;


class CapabilitiesService
{
    public function canOpenRecordingStudio(User $user): bool
    {
        return $user->isRegistered()
            && $user->isVerified()
            && !$user->isExtensionOnly();
    }

    public function canEditVideos(User $user): bool
    {
        return $user->isRegistered()
            && $user->isVerified()
            && !$user->isExtensionOnly();
    }
}
