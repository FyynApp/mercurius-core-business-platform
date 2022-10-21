<?php

namespace App\Controller\Feature\Membership;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Membership\MembershipPlanName;
use App\Entity\Feature\Membership\PaymentProcessor;
use App\Enum\FlashMessageLabel;
use App\Service\Feature\Membership\MembershipService;
use App\Service\Feature\Membership\PaymentProcessorStripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class PaymentProcessorStripeCheckoutController extends AbstractController
{
    public function subscriptionCheckoutStartAction(
        string $planName,
        MembershipService $membershipService,
        PaymentProcessorStripeService $stripeService,
        RouterInterface $router
    ): Response {
        /** @var User $user */
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

    public function subscriptionCheckoutSuccessAction(
        Request $request,
        MembershipService $membershipService,
        PaymentProcessorStripeService $stripeService,
        TranslatorInterface $translator
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $stripeService->handleSubscriptionCheckoutSuccess(
            $user,
            $request->get('planName'),
            $request->get('successHash')
        );

        $this->addFlash(
            FlashMessageLabel::Success->value, $translator->trans('feature.membership.subscription_checkout.success_flash_message')
        );
        return $this->redirectToRoute('feature.membership.overview');
    }

    public function subscriptionCheckoutCancelAction(
        TranslatorInterface $translator
    ): Response
    {
        $this->addFlash(
            FlashMessageLabel::Warning->value, $translator->trans('feature.membership.subscription_checkout.cancel_flash_message')
        );
        return $this->redirectToRoute('feature.membership.overview');
    }
}
