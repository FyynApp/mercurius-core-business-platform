<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\SymfonyEventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\RequestParameter;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


readonly class AppAuthSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface       $router
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        ResponseEvent $event
    ): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if ($session->get(RequestParameter::IsAuthForApp->value)) {
            $token = $this->tokenStorage->getToken();
            if (!is_null($token)) {
                /** @var null|User $user */
                $user = $token->getUser();
                if (!is_null($user)) {
                    if ($user->hasRole(Role::REGISTERED_USER)) {
                        $session->set(RequestParameter::IsAuthForApp->value, false);
                        $response = new RedirectResponse(
                            $this->router->generate(
                                'videobasedmarketing.account.presentation.auth_for_app.success'
                            )
                        );
                        $event->setResponse($response);
                        #throw new Exception($session->getId());
                    }
                }
            }
        }
    }
}
