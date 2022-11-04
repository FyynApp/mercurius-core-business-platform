<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Security;


use App\VideoBasedMarketing\Account\Domain\Entity\User;
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


class UnregisteredUserAuthenticator
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


    public function supports(Request $request): ?bool
    {
        if (   !is_null($request->get('unregisteredUserId'))
            && !is_null($request->get('unregisteredUserAuthHash'))
        ) {
            $this->logger->debug('UnregisteredUserAuthenticator supports this request.');
            return true;
        }

        return false;
    }


    public function authenticate(Request $request): Passport
    {
        $this->logger->debug('Trying to authenticate this request using UnregisteredUserAuthenticator.');

        $unregisteredUserId = $request->get('unregisteredUserId');
        if (is_null($unregisteredUserId)) {
            throw new CustomUserMessageAuthenticationException('Missing unregisteredUserId');
        }

        if (self::generateAuthHash($unregisteredUserId) !== $request->get('unregisteredUserAuthHash')) {
            throw new CustomUserMessageAuthenticationException('Auth hash does not match');
        }

        /** @var ?\App\VideoBasedMarketing\Account\Domain\Entity\User $user */
        $user = $this->entityManager->find(User::class, $unregisteredUserId);

        if (is_null($user)) {
            throw new CustomUserMessageAuthenticationException("No user with id '$unregisteredUserId'.");
        }

        return new SelfValidatingPassport(
            new UserBadge($unregisteredUserId, function () use ($user) {
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
        return new Response('meh.', Response::HTTP_UNAUTHORIZED);
    }


    public static function generateAuthHash(string $userId): string
    {
        return sha1($userId . '1ed39ac2!46f2?6310%b4b7§874ec8e)800d');
    }
}
