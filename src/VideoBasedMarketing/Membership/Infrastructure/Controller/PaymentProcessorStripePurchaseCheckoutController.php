<?php

namespace App\VideoBasedMarketing\Membership\Infrastructure\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Membership\Domain\Entity\Purchase;
use App\VideoBasedMarketing\Membership\Domain\Enum\PackageName;
use App\VideoBasedMarketing\Membership\Domain\Enum\PaymentProcessor;
use App\VideoBasedMarketing\Membership\Domain\Service\PackageService;
use App\VideoBasedMarketing\Membership\Infrastructure\Service\PaymentProcessorStripeService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use ValueError;


class PaymentProcessorStripePurchaseCheckoutController
extends AbstractController
{
    /**
     * @throws ApiErrorException
     * @throws Exception
     */
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/purchase-package/checkout-with-stripe/{packageName}/start',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mitgliedschaft/paket-erwerben/kauf-über-stripe/{packageName}/start',
        ],
        name        : 'videobasedmarketing.membership.infrastructure.purchase.checkout_with_payment_processor_stripe.start',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function purchaseCheckoutStartAction(
        string                        $packageName,
        PackageService                $packageService,
        PaymentProcessorStripeService $stripeService
    ): Response
    {
        $user = $this->getUser();

        if ($user->ownsCurrentlyActiveOrganization()) {
            throw new BadRequestHttpException("Unexpectedly, the user is not the owning user of the currently active organization.");
        }

        $plan = $packageService->getPackageByName(PackageName::from($packageName));
        $paymentProcessor = $packageService->getPaymentProcessorForUser($user);

        if ($paymentProcessor !== PaymentProcessor::Stripe) {
            throw new BadRequestHttpException("Unexpectedly, the payment processor for this user is '$paymentProcessor->value'.");
        }

        return $this->redirect($stripeService->getPurchaseCheckoutUrl(
            $user,
            $plan
        ));
    }

    /**
     * @throws Exception
     */
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/purchase-package/{purchaseId}/checkout-with-stripe/success',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mitgliedschaft/paket-erwerben/{purchaseId}/kauf-über-stripe/erfolg',
        ],
        name        : 'videobasedmarketing.membership.infrastructure.purchase.checkout_with_payment_processor_stripe.success',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function purchaseCheckoutSuccessAction(
        string                        $purchaseId,
        Request                       $request,
        PaymentProcessorStripeService $stripeService,
        TranslatorInterface           $translator,
        EntityManagerInterface        $entityManager
    ): Response
    {
        $purchase = $entityManager->find(Purchase::class, $purchaseId);

        if (is_null($purchase)) {
            throw $this->createNotFoundException("No purchase with id '$purchaseId'.");
        }

        $this->denyAccessUnlessGranted(AccessAttribute::Edit->value, $purchase);

        $success = $stripeService->handlePurchaseCheckoutSuccess(
            $purchase,
            $request->get('purchaseHash')
        );

        if ($success) {
            $this->addFlash(
                FlashMessageLabel::Success->value,
                $translator->trans(
                    'purchase_checkout.success_flash_message',
                    [],
                    'videobasedmarketing.membership'
                )
            );
            return $this->redirectToRoute('shared.presentation.contentpages.homepage');
        } else {
            throw new BadRequestHttpException('Successful checkout return did not result in an active purchase.');
        }
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/membership/purchase-package/{purchaseId}/checkout-with-stripe/cancellation',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mitgliedschaft/paket-erwerben/{purchaseId}/kauf-über-stripe/abbruch',
        ],
        name        : 'videobasedmarketing.membership.infrastructure.purchase.checkout_with_payment_processor_stripe.cancellation',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function purchaseCheckoutCancellationAction(
        TranslatorInterface $translator
    ): Response
    {
        $this->addFlash(
            FlashMessageLabel::Warning->value,
            $translator->trans(
                'purchase_checkout.cancel_flash_message',
                [],
                'videobasedmarketing.membership'
            )
        );
        return $this->redirectToRoute('shared.presentation.contentpages.homepage');
    }
}
