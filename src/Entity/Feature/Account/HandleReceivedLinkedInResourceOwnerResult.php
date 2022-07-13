<?php

namespace App\Entity\Feature\Account;

use InvalidArgumentException;

class HandleReceivedLinkedInResourceOwnerResult
{
    const ERROR_MISSING_ID_OR_EMAIL = 0;
    const ERROR_RETRIEVED_EMAIL_DIFFERS_FROM_STORED_EMAIL = 1;
    const ERROR_THROWABLE = 2;


    public function __construct(bool $successful, ?int $error = null, ?ThirdPartyAuthLinkedinResourceOwner $linkedInResourceOwner = null)
    {
        $this->successful = $successful;

        if (!$successful && is_null($error)) {
            throw new InvalidArgumentException();
        }

        if (!$successful && !is_null($linkedInResourceOwner)) {
            throw new InvalidArgumentException();
        }

        if ($successful && !is_null($error)) {
            throw new InvalidArgumentException();
        }

        if ($successful && is_null($linkedInResourceOwner)) {
            throw new InvalidArgumentException();
        }

        $this->error = $error;
        $this->linkedInResourceOwner = $linkedInResourceOwner;
    }


    private bool $successful;

    public function wasSuccessful(): bool
    {
        return $this->successful;
    }


    private ?int $error = null;

    public function getError(): ?int
    {
        return $this->error;
    }


    private ?ThirdPartyAuthLinkedinResourceOwner $linkedInResourceOwner = null;

    public function getLinkedInResourceOwner(): ?ThirdPartyAuthLinkedinResourceOwner
    {
        return $this->linkedInResourceOwner;
    }
}
