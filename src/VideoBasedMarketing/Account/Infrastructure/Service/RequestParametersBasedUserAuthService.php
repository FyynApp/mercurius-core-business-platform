<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Service;


use App\Shared\Infrastructure\Enum\DateTimeFormat;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\RequestParameter;
use App\VideoBasedMarketing\Account\Infrastructure\Security\RequestParametersBasedUserAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class RequestParametersBasedUserAuthService
{
    private RouterInterface $router;

    public function __construct(
        RouterInterface $router
    )
    {
        $this->router = $router;
    }

    public function createRedirectResponse(
        User   $user,
        string $routeName,
        array  $routeParameters = []
    ): Response
    {
        $validUntil = DateAndTimeService::getDateTimeUtc('+1 minutes');

        return new RedirectResponse(
            $this->router->generate(
                $routeName,
                array_merge(
                    $routeParameters,
                    [
                        RequestParameter::RequestParametersBasedUserAuthId->value =>
                            $user->getId(),

                        RequestParameter::RequestParametersBasedUserAuthValidUntil->value =>
                            $validUntil->format(DateTimeFormat::SecondsSinceUnixEpoch->value),

                        RequestParameter::RequestParametersBasedUserAuthHash->value =>
                            RequestParametersBasedUserAuthenticator::generateAuthHash(
                                $user->getId(),
                                $validUntil
                            )
                    ]
                )
            )
        );
    }
}
