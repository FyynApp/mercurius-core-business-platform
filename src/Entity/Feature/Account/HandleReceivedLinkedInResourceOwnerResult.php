<?php

namespace App\Entity\Feature\Account;

use InvalidArgumentException;

class HandleReceivedLinkedInResourceOwnerResult
{
    const ERROR_MISSING_ID_OR_EMAIL = 0;
    const ERROR_RETRIEVED_EMAIL_DIFFERS_FROM_STORED_EMAIL = 1;

    public function __construct(bool $successful, ?int $error = null, ?ThirdPartyAuthLinkedinResourceOwner $linkedInResourceOwner = null, ?string $loginLinkUrl = null)
    {
        $this->successful = $successful;

        if (!$successful && is_null($error)) {
            throw new InvalidArgumentException();
        }

        if (!$successful && !is_null($linkedInResourceOwner)) {
            throw new InvalidArgumentException();
        }

        if (!$successful && !is_null($loginLinkUrl)) {
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
        $this->loginLinkUrl = $loginLinkUrl;
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


    private ?string $loginLinkUrl = null;

    public function getLoginLinkUrl(): ?string
    {
        return $this->loginLinkUrl;
    }
}
