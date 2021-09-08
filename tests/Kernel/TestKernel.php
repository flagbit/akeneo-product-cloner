<?php

declare(strict_types=1);

namespace Flagbit\Bundle\ProductClonerBundle\Test\Kernel;

require_once __DIR__.'/../../vendor/akeneo/pim-community-dev/src/Kernel.php';

use Kernel;

/**
 * Test kernel used for integration tests
 */
class TestKernel extends Kernel
{
    /**
     * {@inheritDoc}
     *
     * @return iterable
     */
    public function registerBundles(): iterable
    {
        $bundles = require __DIR__ . '/../../vendor/akeneo/pim-community-dev/config/bundles.php';
        $bundles += require __DIR__ . '/config/bundles.php';

        foreach ($bundles as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getRootDir(): string
    {
        return __DIR__;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getProjectDir(): string
    {
        return __DIR__;
    }
}
