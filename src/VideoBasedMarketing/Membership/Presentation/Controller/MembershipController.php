<?php

namespace App\VideoBasedMarketing\Membership\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;
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
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/upgrade-offer-for-capabilities',
            'de' => '%app.routing.route_prefix.with_locale.protected.en%/mitgliedschaft/upgrade-angebot-für-funktionalitäten',
        ],
        name        : 'videobasedmarketing.membership.presentation.show_upgrade_offer.for_capabilities',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showUpgradeOfferForCapabilities(
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

        $membershipPlan = $membershipService
            ->getCheapestMembershipPlanRequiredForCapabilities($capabilities);

        if (is_null($membershipPlan)) {
            $this->addFlash(
                FlashMessageLabel::Warning->value,
                $translator->trans(
                    'upgrade_offer.error_no_plan_found',
                    [],
                    'videobasedmarketing.membership'
                )
            );
            $this->redirectToRoute('shared.presentation.contentpages.homepage');
        }

        return $this->render(
            '@videobasedmarketing.membership/upgrade_offer.html.twig',
            [
                'capabilities' => $capabilities,
                'membershipPlan' => $membershipPlan,
                'currentMembershipPlan' => $membershipService->getCurrentlySubscribedMembershipPlanForUser($user)
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/upgrade_offer_for_plan',
            'de' => '%app.routing.route_prefix.with_locale.protected.en%/mitgliedschaft/upgrade-angebot-für-paket',
        ],
        name        : 'videobasedmarketing.membership.presentation.show_upgrade_offer.for_plan',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showUpgradeOfferForPlan(
        Request           $request,
        MembershipService $membershipService
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $membershipPlanNameValue = $request->get('membershipPlanName');
        $membershipPlanName = MembershipPlanName::tryFrom($membershipPlanNameValue);

        if (is_null($membershipPlanName)) {
            throw $this->createNotFoundException("No membership plan name '$membershipPlanNameValue'.");
        }

        $membershipPlan = $membershipService
            ->getMembershipPlanByName($membershipPlanName);

        return $this->render(
            '@videobasedmarketing.membership/upgrade_offer.html.twig',
            [
                'membershipPlan' => $membershipPlan,
                'currentMembershipPlan' => $membershipService->getCurrentlySubscribedMembershipPlanForUser($user)
            ]
        );
    }
}
