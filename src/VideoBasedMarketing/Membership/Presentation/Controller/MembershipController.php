<?php

namespace App\VideoBasedMarketing\Membership\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Enum\PaymentProcessor;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;


class MembershipController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/overview',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mitgliedschaft/überblick',
        ],
        name        : 'videobasedmarketing.membership.presentation.overview',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function overviewAction(MembershipService $membershipService): Response
    {
        $user = $this->getUser();

        return $this->render(
            '@videobasedmarketing.membership/overview.html.twig',
            [
                'isSubscribed' => $membershipService->userIsSubscribedToPlanThatMustBeBought($user),
                'currentPlan' => $membershipService->getCurrentlySubscribedMembershipPlanForUser($user),
                'availablePlans' => $membershipService->getAvailablePlansForUser($user)
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/subscription/checkout/{planName}/start',
            'de' => '%app.routing.route_prefix.with_locale.protected.en%/mitgliedschaft/abonnement/kauf/{planName}/start',
        ],
        name        : 'videobasedmarketing.membership.presentation.subscription.checkout.start',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function subscriptionCheckoutStartAction(
        string                                                                $planName,
        MembershipService $membershipService,
    ): Response {
        $user = $this->getUser();

        $paymentProcessor = $membershipService->getPaymentProcessorForUser($user);

        if ($paymentProcessor === PaymentProcessor::Stripe) {
            return $this->redirectToRoute(
                'videobasedmarketing.membership.infrastructure.subscription.checkout_with_payment_processor_stripe.start',
                ['planName' => $planName]
            );
        } else {
            throw new NotImplementedException("Cannot handle payment processor $paymentProcessor->value.");
        }
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/plan-offering-for-capabilities',
            'de' => '%app.routing.route_prefix.with_locale.protected.en%/mitgliedschaft/paket-angebot-für-funktionalitäten',
        ],
        name        : 'videobasedmarketing.membership.presentation.show_plan_offering_for_capabilities',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showPlanOfferingForCapabilities(
        Request             $request,
        MembershipService   $membershipService,
        TranslatorInterface $translator
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $capabilityNames = $request->get('capabilityNames');

        $capabilities = [];

        foreach ($capabilityNames as $capabilityName) {
            if (is_null(Capability::tryFrom($capabilityName))) {
                throw new BadRequestHttpException("Unknown capability name '$capabilityName'.");
            }
            $capabilities[] = Capability::from($capabilityName);
        }

        if (sizeof($capabilities) < 1) {
            throw new BadRequestHttpException("Got zero capabilities.");
        }

        $cheapestMembershipPlan = $membershipService
            ->getCheapestMembershipPlanRequiredForCapabilities($capabilities);

        if (is_null($cheapestMembershipPlan)) {
            $this->addFlash(
                FlashMessageLabel::Warning->value,
                $translator->trans(
                    'plan_offering_for_capabilities.error_no_plan_found',
                    [],
                    'videobasedmarketing.membership'
                )
            );
            $this->redirectToRoute('shared.presentation.contentpages.homepage');
        }

        return $this->render(
            '@videobasedmarketing.membership/plan_offering_for_capabilities.html.twig',
            [
                'capabilities' => $capabilities,
                'cheapestMembershipPlan' => $cheapestMembershipPlan,
                'currentMembershipPlan' => $membershipService->getCurrentlySubscribedMembershipPlanForUser($user)
            ]
        );
    }
}
