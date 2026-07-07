<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    // `sylius/core-bundle` is a virtual package provided by the `sylius/sylius` monorepo, so the
    // analyser attributes the CoreBundle classes we use (e.g. SyliusPluginTrait) to `sylius/sylius`
    // instead of to `sylius/core-bundle`. Keep `sylius/core-bundle` as the canonical Sylius
    // dependency and silence the shadow/unused noise this packaging split produces.
    ->ignoreErrorsOnPackage('sylius/sylius', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/core-bundle', [ErrorType::UNUSED_DEPENDENCY])
;
