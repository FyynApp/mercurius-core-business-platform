<?php

namespace App\Controller\Feature\Membership;

use App\Entity\Feature\Account\User;
use App\Enum\FlashMessageLabel;
use App\Service\Feature\Membership\MembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;


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
                'currentPlan' => $membershipService->getMembershipPlanForUser($user),
                'availablePlans' => $membershipService->getAvailablePlansForUser($user)
            ]
        );
    }

    public function subscriptionCheckoutSuccessAction(
        Request $request,
        MembershipService $membershipService,
        TranslatorInterface $translator
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $membershipService->handleSubscriptionCheckoutSuccess(
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
