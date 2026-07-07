<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // The monorepo dev setup and CI's split-package resolution disagree on which ignores apply
    ->disableReportingUnmatchedIgnores()
    ->addPathToExclude(__DIR__ . '/tests')
    // The Sylius monorepo (sylius/sylius) is installed for development and `replace`s the split
    // packages this plugin actually depends on (sylius/core-bundle and its components), so class
    // usages resolve to the monorepo package.
    ->ignoreErrorsOnPackage('sylius/sylius', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/core-bundle', [ErrorType::UNUSED_DEPENDENCY])
    // The split components the plugin directly uses: under the monorepo dev install their
    // classes resolve to sylius/sylius, so they look unused locally while being real
    // dependencies of a split-package install.
    ->ignoreErrorsOnPackages([
        'sylius/channel',
        'sylius/channel-bundle',
        'sylius/core',
        'sylius/customer',
        'sylius/order',
        'sylius/promotion',
        'sylius/review',
        'sylius/ui-bundle',
        'sylius/user',
    ], [ErrorType::UNUSED_DEPENDENCY])
    // The bundle base class comes from http-kernel; it is only referenced transitively but is a
    // real runtime requirement of any Symfony bundle.
    ->ignoreErrorsOnPackage('symfony/http-kernel', [ErrorType::UNUSED_DEPENDENCY])
;
