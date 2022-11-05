<?php

namespace App\VideoBasedMarketing\Membership\Presentation\Controller;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Entity\PaymentProcessor;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;


class MembershipController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '{_locale}/membership/overview',
            'de' => '{_locale}/mitgliedschaft/überblick',
        ],
        name        : 'videobasedmarketing.membership.overview',
        requirements: ['_locale' => '%app.route_locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function overviewAction(MembershipService $membershipService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render(
            '@videobasedmarketing.membership/overview.html.twig',
            [
                'isSubscribed' => $membershipService->userIsSubscribed($user),
                'currentPlan' => $membershipService->getCurrentlySubscribedMembershipPlanForUser($user),
                'availablePlans' => $membershipService->getAvailablePlansForUser($user)
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '{_locale}/membership/subscription/checkout/{planName}/start',
            'de' => '{_locale}/mitgliedschaft/abonnement/abschluss/{planName}/starten',
        ],
        name        : 'videobasedmarketing.membership.subscription.checkout.start',
        requirements: ['_locale' => '%app.route_locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function subscriptionCheckoutStartAction(
        string                                                                $planName,
        MembershipService $membershipService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $paymentProcessor = $membershipService->getPaymentProcessorForUser($user);

        if ($paymentProcessor === PaymentProcessor::Stripe) {
            return $this->redirectToRoute(
                'bounded_context.membership.subscription.checkout_with_payment_processor_stripe.start',
                ['planName' => $planName]
            );
        } else {
            throw new NotImplementedException("Cannot handle payment processor $paymentProcessor->value.");
        }
    }
}
