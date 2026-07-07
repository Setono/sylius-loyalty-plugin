<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type;

use Setono\SyliusLoyaltyPlugin\Expression\ExpressionCatalogInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * An ExpressionLanguage input rendered as a CodeMirror 6 editor (never a bare textarea): the
 * sandbox catalog is serialized into the data-catalog attribute — the single source that
 * drives autocompletion, the reference panel, and (server-side) validation — and the editor
 * lints through the admin XHR route on change.
 *
 * @extends AbstractType<string|null>
 */
final class ExpressionType extends AbstractType
{
    public function __construct(
        private readonly ExpressionCatalogInterface $catalog,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $cdnBaseUrl,
    ) {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var string|null $trigger */
        $trigger = $options['trigger'];

        /** @var array<string, mixed> $vars */
        $vars = $view->vars;

        /** @var array<string, mixed> $attr */
        $attr = $vars['attr'] ?? [];

        $vars['attr'] = array_merge($attr, [
            'data-setono-sylius-loyalty-expression' => '1',
            'data-catalog' => (string) json_encode($this->catalog->toArray(), \JSON_THROW_ON_ERROR),
            'data-lint-url' => $this->urlGenerator->generate('setono_sylius_loyalty_admin_expression_lint'),
            'data-cdn-base-url' => $this->cdnBaseUrl,
            'data-trigger' => $trigger ?? '',
            'rows' => 3,
        ]);

        $view->vars = $vars;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'trigger' => null,
            'required' => false,
        ]);
        $resolver->setAllowedTypes('trigger', ['string', 'null']);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_loyalty_expression';
    }
}
