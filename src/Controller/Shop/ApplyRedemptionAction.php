<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Shop;

use Doctrine\Persistence\ObjectManager;
use Setono\SyliusLoyaltyPlugin\Form\Type\RedemptionType;
use Setono\SyliusLoyaltyPlugin\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApplyRedemptionAction
{
    public function __construct(
        private readonly CartContextInterface $cartContext,
        private readonly FormFactoryInterface $formFactory,
        private readonly OrderProcessorInterface $orderProcessor,
        private readonly ObjectManager $orderManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $cart = $this->cartContext->getCart();

        if ($cart instanceof OrderInterface) {
            $form = $this->formFactory->create(RedemptionType::class, $cart);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->orderProcessor->process($cart);
                $this->orderManager->flush();

                $session = $request->getSession();
                if ($session instanceof FlashBagAwareSessionInterface) {
                    $session->getFlashBag()->add('success', $this->translator->trans('setono_sylius_loyalty.cart.redemption_updated'));
                }
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('sylius_shop_cart_summary'));
    }
}
