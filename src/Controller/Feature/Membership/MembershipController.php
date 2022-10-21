<?php

namespace App\Controller\Feature\Membership;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Membership\PaymentProcessor;
use App\Service\Feature\Membership\MembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;


class MembershipController extends AbstractController
{
    public function overviewAction(MembershipService $membershipService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render(
            'feature/membership/overview.html.twig',
            [
                'isSubscribed' => $membershipService->userIsSubscribed($user),
                'currentPlan' => $membershipService->getCurrentlySubscribedMembershipPlanForUser($user),
                'availablePlans' => $membershipService->getAvailablePlansForUser($user)
            ]
        );
    }

    public function subscriptionCheckoutStartAction(
        string $planName,
        MembershipService $membershipService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $paymentProcessor = $membershipService->getPaymentProcessorForUser($user);

        if ($paymentProcessor === PaymentProcessor::Stripe) {
            return $this->redirectToRoute(
                'feature.membership.subscription.checkout_with_payment_processor_stripe.start',
                ['planName' => $planName]
            );
        } else {
            throw new NotImplementedException("Cannot handle payment processor $paymentProcessor->value.");
        }
    }
}
