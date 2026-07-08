<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Controller\Shop;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Controller\Shop\ApplyRedemptionAction;
use Setono\SyliusLoyaltyPlugin\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApplyRedemptionActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_recalculates_and_redirects_when_the_form_is_submitted(): void
    {
        $cart = $this->prophesize(OrderInterface::class)->reveal();

        $form = $this->prophesize(FormInterface::class);
        $form->isSubmitted()->willReturn(true);
        $form->isValid()->willReturn(true);
        $revealedForm = $form->reveal();
        $form->handleRequest(Argument::any())->willReturn($revealedForm);

        $formFactory = $this->prophesize(FormFactoryInterface::class);
        $formFactory->create(Argument::cetera())->willReturn($revealedForm);

        $cartContext = $this->prophesize(CartContextInterface::class);
        $cartContext->getCart()->willReturn($cart);

        $orderProcessor = $this->prophesize(OrderProcessorInterface::class);
        $orderProcessor->process($cart)->shouldBeCalled();

        $orderManager = $this->prophesize(ObjectManager::class);
        $orderManager->flush()->shouldBeCalled();

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('sylius_shop_cart_summary')->willReturn('/cart');

        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans(Argument::cetera())->willReturn('Updated');

        $action = new ApplyRedemptionAction(
            $cartContext->reveal(),
            $formFactory->reveal(),
            $orderProcessor->reveal(),
            $orderManager->reveal(),
            $urlGenerator->reveal(),
            $translator->reveal(),
        );

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $response = $action($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/cart', $response->getTargetUrl());
    }

    /**
     * @test
     */
    public function it_only_redirects_when_the_cart_is_not_a_loyalty_order(): void
    {
        $cartContext = $this->prophesize(CartContextInterface::class);
        $cartContext->getCart()->willReturn($this->prophesize(\Sylius\Component\Order\Model\OrderInterface::class)->reveal());

        $orderProcessor = $this->prophesize(OrderProcessorInterface::class);
        $orderProcessor->process(Argument::any())->shouldNotBeCalled();

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('sylius_shop_cart_summary')->willReturn('/cart');

        $action = new ApplyRedemptionAction(
            $cartContext->reveal(),
            $this->prophesize(FormFactoryInterface::class)->reveal(),
            $orderProcessor->reveal(),
            $this->prophesize(ObjectManager::class)->reveal(),
            $urlGenerator->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
        );

        $response = $action(new Request());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/cart', $response->getTargetUrl());
    }
}
