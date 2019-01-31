<?php

namespace Flagbit\Bundle\ProductClonerBundle;

use Flagbit\Bundle\ProductClonerBundle\DependencyInjection\Compiler\LegacyServicePass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FlagbitProductClonerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new LegacyServicePass());
    }
}
