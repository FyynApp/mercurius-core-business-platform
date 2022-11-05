<?php

namespace App\VideoBasedMarketing\Membership\Infrastructure\Controller;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Entity\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Entity\PaymentProcessor;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\Enum\FlashMessageLabel;
use App\Security\VotingAttribute;
use App\VideoBasedMarketing\Membership\Infrastructure\Service\PaymentProcessorStripeService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            'en' => '{_locale}/membership/overview',
            'de' => '{_locale}/mitgliedschaft/überblick',
        ],
        name        : 'videobasedmarketing.membership.subscription.checkout_with_payment_processor_stripe.start',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function subscriptionCheckoutStartAction(
        string                        $planName,
        MembershipService             $membershipService,
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
        $subscription = $entityManager->find(\App\VideoBasedMarketing\Membership\Domain\Entity\Subscription::class, $subscriptionId);

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
                FlashMessageLabel::Success->value, $translator->trans('bounded_context.membership.subscription_checkout.success_flash_message')
            );
            return $this->redirectToRoute('bounded_context.membership.overview');
        } else {
            throw new BadRequestHttpException('Successful checkout return did not result in an active subscription.');
        }
    }

    public function subscriptionCheckoutCancelAction(
        TranslatorInterface $translator
    ): Response
    {
        $this->addFlash(
            FlashMessageLabel::Warning->value, $translator->trans('bounded_context.membership.subscription_checkout.cancel_flash_message')
        );
        return $this->redirectToRoute('bounded_context.membership.overview');
    }
}
