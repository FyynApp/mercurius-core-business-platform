<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Service;


use App\Shared\Infrastructure\Enum\DateTimeFormat;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\RequestParameter;
use App\VideoBasedMarketing\Account\Infrastructure\Security\RequestParametersBasedUserAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        return new RedirectResponse(
            $this->createUrl(
                $user,
                $routeName,
                $routeParameters
            )
        );
    }

    public function createUrl(
        User   $user,
        string $routeName,
        array  $routeParameters = [],
        int    $referenceType = UrlGeneratorInterface::RELATIVE_PATH
    ): string
    {
        $validUntil = DateAndTimeService::getDateTime('+1 minutes');

        return $this->router->generate(
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
            ),
            $referenceType
        );
    }

    public function isAuthRequest(
        Request $request
    ): bool
    {
        if (   !is_null($request->get(RequestParameter::RequestParametersBasedUserAuthId->value))
            && !is_null($request->get(RequestParameter::RequestParametersBasedUserAuthHash->value))
            && !is_null($request->get(RequestParameter::RequestParametersBasedUserAuthValidUntil->value))
        ) {
            return true;
        }

        return false;
    }
}
