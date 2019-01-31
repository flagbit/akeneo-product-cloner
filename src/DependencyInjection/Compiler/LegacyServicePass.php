<?php
/**
 * TODO: remove this manipulation when the support for 2.1 is dropped
 */

namespace Flagbit\Bundle\ProductClonerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LegacyServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        if (!$container->hasDefinition('pim_catalog.builder.variant_product')) {
            $productControllerService = $container->getDefinition('flagbit_product_cloner.controller.product');

            foreach ($productControllerService->getArguments() as $key => $argument) {
                if ((string)$argument === 'pim_catalog.builder.variant_product') {
                    $productControllerService->setArgument($key, new Reference('pim_catalog.builder.product'));
                }
            }
        }
    }
}
