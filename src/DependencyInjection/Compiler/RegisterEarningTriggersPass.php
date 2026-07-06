<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\DependencyInjection\Compiler;

use Setono\SyliusLoyaltyPlugin\Event\Trigger\CustomerBirthdayTriggerEvent;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\CustomerRegisteredTriggerEvent;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\ProductReviewApprovedTriggerEvent;
use Setono\SyliusLoyaltyPlugin\EventListener\EarningTriggerListener;
use Setono\SyliusLoyaltyPlugin\Exception\InvalidTriggerException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers the earning trigger event classes: the plugin's built-ins plus those configured
 * under "setono_sylius_loyalty.triggers". Symfony dispatches by the event's concrete class
 * name, so a listener on the abstract base would never receive subclasses — this pass
 * therefore tags the plugin's handler once per concrete event class, validates inheritance and
 * trigger-code uniqueness, and builds the trigger catalog (code, label, typed context
 * properties) that feeds the rule form and the expression editor.
 *
 * Must run before Symfony's RegisterListenersPass (registered with a positive priority).
 */
final class RegisterEarningTriggersPass implements CompilerPassInterface
{
    private const BUILT_IN_TRIGGERS = [
        CustomerRegisteredTriggerEvent::class,
        ProductReviewApprovedTriggerEvent::class,
        CustomerBirthdayTriggerEvent::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(EarningTriggerListener::class)) {
            return;
        }

        /** @var list<class-string> $configuredTriggers */
        $configuredTriggers = $container->getParameter('setono_sylius_loyalty.triggers');

        $triggers = array_values(array_unique([...self::BUILT_IN_TRIGGERS, ...$configuredTriggers]));

        $listener = $container->getDefinition(EarningTriggerListener::class);

        $catalog = [];
        foreach ($triggers as $trigger) {
            $code = self::validate($trigger, $catalog);

            $catalog[$code] = [
                'class' => $trigger,
                'label' => $trigger::getLabel(),
                'context' => self::contextProperties($trigger),
            ];

            $listener->addTag('kernel.event_listener', [
                'event' => $trigger,
                'method' => '__invoke',
            ]);
        }

        $container->setParameter('setono_sylius_loyalty.trigger_catalog', $catalog);
    }

    /**
     * @param array<string, mixed> $catalog
     *
     * @phpstan-assert class-string<EarningTriggerEvent> $trigger
     *
     * @return string the trigger code
     */
    private static function validate(string $trigger, array $catalog): string
    {
        if (!class_exists($trigger)) {
            throw new InvalidTriggerException(sprintf('The configured trigger "%s" does not exist', $trigger));
        }

        if (!is_subclass_of($trigger, EarningTriggerEvent::class)) {
            throw new InvalidTriggerException(sprintf(
                'The configured trigger "%s" must extend %s',
                $trigger,
                EarningTriggerEvent::class,
            ));
        }

        if ((new \ReflectionClass($trigger))->isAbstract()) {
            throw new InvalidTriggerException(sprintf('The configured trigger "%s" must not be abstract', $trigger));
        }

        $code = $trigger::getTriggerCode();
        if (isset($catalog[$code])) {
            throw new InvalidTriggerException(sprintf(
                'The trigger code "%s" of "%s" collides with the already registered "%s"',
                $code,
                $trigger,
                is_array($catalog[$code]) && is_string($catalog[$code]['class'] ?? null) ? $catalog[$code]['class'] : '?',
            ));
        }

        return $code;
    }

    /**
     * The subclass's own public properties are the trigger's typed expression context.
     *
     * @param class-string<EarningTriggerEvent> $trigger
     *
     * @return array<string, string> property name => type name
     */
    private static function contextProperties(string $trigger): array
    {
        $properties = [];
        foreach ((new \ReflectionClass($trigger))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (EarningTriggerEvent::class === $property->getDeclaringClass()->getName()) {
                continue;
            }

            $type = $property->getType();

            $properties[$property->getName()] = $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed';
        }

        return $properties;
    }
}
