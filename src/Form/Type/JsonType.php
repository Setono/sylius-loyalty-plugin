<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * A textarea holding a JSON object, transformed to/from an array. The structured per-type
 * configuration subforms replace most uses of this in the rule form; it remains the generic
 * fallback for configuration payloads.
 *
 * @extends AbstractType<mixed>
 */
final class JsonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static function (mixed $value): string {
                if (null === $value || [] === $value) {
                    return '{}';
                }

                return (string) json_encode($value, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
            },
            static function (mixed $value): array {
                if (!is_string($value) || '' === trim($value)) {
                    return [];
                }

                try {
                    /** @var mixed $decoded */
                    $decoded = json_decode($value, true, 32, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new TransformationFailedException($e->getMessage(), 0, $e, 'setono_sylius_loyalty.form.invalid_json');
                }

                if (!is_array($decoded)) {
                    throw new TransformationFailedException('Expected a JSON object', 0, null, 'setono_sylius_loyalty.form.invalid_json');
                }

                return $decoded;
            },
        ));
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_loyalty_json';
    }
}
