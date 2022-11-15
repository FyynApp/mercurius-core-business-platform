<?php

namespace App\VideoBasedMarketing\Membership\Infrastructure\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Enum\PaymentProcessor;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\VideoBasedMarketing\Membership\Infrastructure\Service\PaymentProcessorStripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class PaymentProcessorStripeCheckoutController
extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/subscription/checkout-with-stripe/{planName}/start',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mitgliedschaft/abonnement/kauf-über-stripe/{planName}/start',
        ],
        name        : 'videobasedmarketing.membership.infrastructure.subscription.checkout_with_payment_processor_stripe.start',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function subscriptionCheckoutStartAction(
        string                        $planName,
        MembershipService             $membershipService,
        PaymentProcessorStripeService $stripeService
    ): Response {
        $user = $this->getUser();

        $plan = $membershipService->getMembershipPlanByName(MembershipPlanName::from($planName));
        $paymentProcessor = $membershipService->getPaymentProcessorForUser($user);

        if ($paymentProcessor !== PaymentProcessor::Stripe) {
            throw new BadRequestHttpException("Unexpectedly, the payment processor for this user is '$paymentProcessor->value'.");
        }

        return $this->redirect($stripeService->getSubscriptionCheckoutUrl(
            $user,
            $plan
        ));
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/subscription/{subscriptionId}/checkout-with-stripe/success',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mitgliedschaft/abonnement/{subscriptionId}/kauf-über-stripe/erfolg',
        ],
        name        : 'videobasedmarketing.membership.infrastructure.subscription.checkout_with_payment_processor_stripe.success',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function subscriptionCheckoutSuccessAction(
        string $subscriptionId,
        Request $request,
        PaymentProcessorStripeService $stripeService,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager
    ): Response
    {
        $subscription = $entityManager->find(\App\VideoBasedMarketing\Membership\Domain\Entity\Subscription::class, $subscriptionId);

        if (is_null($subscription)) {
            throw $this->createNotFoundException("No subscription with id '$subscriptionId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Edit->value, $subscription);

        $success = $stripeService->handleSubscriptionCheckoutSuccess(
            $subscription,
            $request->get('subscriptionHash')
        );

        if ($success) {
            $this->addFlash(
                FlashMessageLabel::Success->value,
                $translator->trans(
                    'subscription_checkout.success_flash_message',
                    [],
                    'videobasedmarketing.membership'
                )
            );
            return $this->redirectToRoute('videobasedmarketing.membership.presentation.overview');
        } else {
            throw new BadRequestHttpException('Successful checkout return did not result in an active subscription.');
        }
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/subscription/{subscriptionId}/checkout-with-stripe/cancellation',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mitgliedschaft/abonnement/{subscriptionId}/kauf-über-stripe/abbruch',
        ],
        name        : 'videobasedmarketing.membership.infrastructure.subscription.checkout_with_payment_processor_stripe.cancellation',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function subscriptionCheckoutCancellationAction(
        TranslatorInterface $translator
    ): Response
    {
        $this->addFlash(
            FlashMessageLabel::Warning->value,
            $translator->trans(
                'subscription_checkout.cancel_flash_message',
                [],
                'videobasedmarketing.membership'
            )
        );
        return $this->redirectToRoute('videobasedmarketing.membership.presentation.overview');
    }
}
