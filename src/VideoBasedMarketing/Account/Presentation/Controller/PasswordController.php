<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class PasswordController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/account/change-password',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/benutzerkonto/passwort-ändern',
        ],
        name        : 'videobasedmarketing.account.presentation.password.change',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function changeAction(): Response
    {
        return $this->render(
            '@videobasedmarketing.account/password/change.html.twig'
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/account/change-password/handle',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/benutzerkonto/passwort-ändern/verarbeiten',
        ],
        name        : 'videobasedmarketing.account.presentation.password.handle_change',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function handleChangeAction(
        Request                               $request,
        AccountDomainService                  $accountDomainService,
        TranslatorInterface                   $translator,
        RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService
    ): Response
    {
        if (!$this->isCsrfTokenValid('password-change', $request->get('_csrf_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $plainPassword = $request->get('plainPassword');
        $plainPasswordRepeat = $request->get('plainPasswordRepeat');

        if (is_null($plainPassword) || is_null($plainPasswordRepeat)) {
            throw new BadRequestHttpException();
        }

        if ($plainPassword !== $plainPasswordRepeat) {
            $this->addFlash(
                FlashMessageLabel::Warning->value,
                $translator->trans('password.change.error.not_identical', [], 'videobasedmarketing.account')
            );

            return $this->redirectToRoute(
                'videobasedmarketing.account.presentation.password.change',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        if (mb_strlen($plainPassword) < 6 || mb_strlen($plainPassword) > 4096) {
            $this->addFlash(
                FlashMessageLabel::Warning->value,
                $translator->trans('password.change.error.too_short', [], 'videobasedmarketing.account')
            );

            return $this->redirectToRoute(
                'videobasedmarketing.account.presentation.password.change',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        $accountDomainService->updatePassword($this->getUser(), $plainPassword);

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $translator->trans('password.change.success_message', [], 'videobasedmarketing.account')
        );

        return $requestParametersBasedUserAuthService->createRedirectResponse(
            $this->getUser(),
            'shared.presentation.contentpages.homepage',
        );
    }
}
