<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Security;


use App\Shared\Infrastructure\Enum\DateTimeFormat;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\RequestParameter;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;


class RequestParametersBasedUserAuthenticator
    extends AbstractAuthenticator
{
    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;


    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }


    public function supports(
        Request $request
    ): ?bool
    {
        if (   !is_null($request->get(RequestParameter::RequestParametersBasedUserAuthId->value))
            && !is_null($request->get(RequestParameter::RequestParametersBasedUserAuthHash->value))
            && !is_null($request->get(RequestParameter::RequestParametersBasedUserAuthValidUntil->value))
        ) {
            $validUntilDateTime = DateAndTimeService::getDateTimeUtc();
            $validUntilDateTime->setTimestamp(
                (int)$request->get(RequestParameter::RequestParametersBasedUserAuthValidUntil->value)
            );

            if ($validUntilDateTime < DateAndTimeService::getDateTimeUtc()) {
                return false;
            }

            $this->logger->debug('RequestParametersBasedUserAuthenticator supports this request.');
            return true;
        }

        return false;
    }


    public function authenticate(
        Request $request
    ): Passport
    {
        $this->logger->debug('Trying to authenticate this request using RequestParametersBasedUserAuthenticator.');

        $validUntil = $request->get(RequestParameter::RequestParametersBasedUserAuthValidUntil->value);

        if (is_null($validUntil)) {
            throw new CustomUserMessageAuthenticationException(
                'Missing ' . RequestParameter::RequestParametersBasedUserAuthValidUntil->value
            );
        }

        $validUntilDateTime = DateAndTimeService::getDateTimeUtc();
        $validUntilDateTime->setTimestamp((int)$validUntil);

        if ($validUntilDateTime < DateAndTimeService::getDateTimeUtc()) {
            throw new CustomUserMessageAuthenticationException(
                'validUntil has already passed at '
                . $validUntilDateTime->format(DateTimeFormat::Iso8601->value)
                . '.'
            );
        }

        $userId = $request->get(RequestParameter::RequestParametersBasedUserAuthId->value);
        if (is_null($userId)) {
            throw new CustomUserMessageAuthenticationException(
                'Missing ' . RequestParameter::RequestParametersBasedUserAuthId->value
            );
        }

        if (self::generateAuthHash($userId, $validUntilDateTime)
            !== $request->get(RequestParameter::RequestParametersBasedUserAuthHash->value)
        ) {
            throw new CustomUserMessageAuthenticationException('Auth hash does not match');
        }

        /** @var ?User $user */
        $user = $this->entityManager->find(User::class, $userId);

        if (is_null($user)) {
            throw new CustomUserMessageAuthenticationException("No user with id '$userId'.");
        }

        return new SelfValidatingPassport(
            new UserBadge($userId, function () use ($user) {
                return $user;
            }),
            [new RememberMeBadge()]
        );
    }


    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response
    {
        return null;
    }


    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response
    {
        return new Response(
            'Could not authenticate based on provided parameters.',
            Response::HTTP_UNAUTHORIZED
        );
    }


    public static function generateAuthHash(
        string $userId,
        DateTime $validUntil
    ): string
    {
        return sha1(
            $userId
            . $validUntil->format(DateTimeFormat::SecondsSinceUnixEpoch->value)
            . '1ed39ac2!46f2?6310%b4b7§874ec8e)800d'
        );
    }
}
