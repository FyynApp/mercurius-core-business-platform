<?php

namespace App\Shared\Infrastructure\Message;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use ValueError;

class ClearTusCacheCommandMessage
    implements AsyncMessageInterface
{
    private string $userId;

    private string $token;

    public function __construct(
        User   $user,
        string $token
    )
    {
        if (is_null($user->getId())) {
            throw new ValueError('user needs an id.');
        }
        $this->userId = $user->getId();
        $this->token = $token;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
