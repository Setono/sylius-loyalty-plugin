<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for functional tests running against the Sylius test application in tests/Application.
 *
 * Database state is isolated per test by dama/doctrine-test-bundle (each test runs inside a
 * transaction that is rolled back). Tests that need real commits (e.g. cross-process locking
 * scenarios) must opt out via DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver and clean up
 * after themselves.
 */
abstract class FunctionalTestCase extends KernelTestCase
{
    protected function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
