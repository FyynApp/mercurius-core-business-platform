<?php

namespace App\Controller\Feature\Membership;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Membership\MembershipPlanName;
use App\Entity\Feature\Membership\PaymentProcessor;
use App\Entity\Feature\Membership\Subscription;
use App\Enum\FlashMessageLabel;
use App\Security\VotingAttribute;
use App\Service\Feature\Membership\MembershipService;
use App\Service\Feature\Membership\PaymentProcessorStripeService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;


class PaymentProcessorStripeCheckoutController extends AbstractController
{
    public function subscriptionCheckoutStartAction(
        string $planName,
        MembershipService $membershipService,
        PaymentProcessorStripeService $stripeService
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

    /**
     * @throws Exception
     */
    public function subscriptionCheckoutSuccessAction(
        string $subscriptionId,
        Request $request,
        PaymentProcessorStripeService $stripeService,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager
    ): Response
    {
        $subscription = $entityManager->find(Subscription::class, $subscriptionId);

        if (is_null($subscription)) {
            throw new NotFoundHttpException("No subscription with id '$subscriptionId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Edit->value, $subscription);

        $success = $stripeService->handleSubscriptionCheckoutSuccess(
            $subscription,
            $request->get('subscriptionHash')
        );

        if ($success) {
            $this->addFlash(
                FlashMessageLabel::Success->value, $translator->trans('feature.membership.subscription_checkout.success_flash_message')
            );
            return $this->redirectToRoute('feature.membership.overview');
        } else {
            throw new BadRequestHttpException('Successful checkout return did not result in an active subscription.');
        }
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
