<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;


readonly class LoginBasedUserAuthenticator
    implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    )
    {
    }

    public function start(
        Request                 $request,
        AuthenticationException $authException = null
    ): Response
    {
        return new RedirectResponse(
            $this->urlGenerator->generate('videobasedmarketing.account.presentation.sign_in')
        );
    }
}
