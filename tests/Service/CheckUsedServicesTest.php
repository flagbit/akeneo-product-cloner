<?php

declare(strict_types=1);

namespace Flagbit\Bundle\ProductClonerBundle\Test\Service;

use Akeneo\Pim\Enrichment\Component\Product\Builder\ProductBuilderInterface;
use Akeneo\Pim\Enrichment\Component\Product\Comparator\Filter\FilterInterface;
use Akeneo\Pim\Enrichment\Component\Product\Converter\ConverterInterface;
use Akeneo\Pim\Enrichment\Component\Product\Localization\Localizer\AttributeConverterInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductModelRepositoryInterface;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Tool\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Tool\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Akeneo\UserManagement\Bundle\Context\UserContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Test to ensure the services used by the bundle are available in the currently
 * used Akeneo version. This makes future updates easier, as this test can provide
 * a first indication if something critical may have changed. It does not replace manual
 * testing, as it only checks for service availability and not if the services undergone
 * a significant change.
 */
class CheckUsedServicesTest extends KernelTestCase
{
    /**
     * Boot symfony kernel before executing any tests
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @dataProvider usedServicesDataProvider
     *
     * @param string $serviceName
     * @param string $expectedType
     */
    public function testUsedServiceIsAvailable(string $serviceName, string $expectedType): void {
        self::assertTrue(
            static::$container->has($serviceName),
            sprintf('Service %s seems to not exist but is required by the bundle!', $serviceName)
        );
        self::assertInstanceOf(
            $expectedType,
            static::$container->get($serviceName),
            sprintf('Service %s must be an instance of %s!', $serviceName, $expectedType)
        );
    }

    /**
     * @return string[][]
     */
    public function usedServicesDataProvider(): array {
        return  [
            // Used in both places
            'Attribute Repository' => [
                'pim_catalog.repository.attribute',
                AttributeRepositoryInterface::class
            ],
            'External API Serializer' => [
                'pim_external_api_serializer',
                NormalizerInterface::class
            ],
            'Product Validator' => [
                'pim_catalog.validator.product',
                ValidatorInterface::class
            ],
            'Normalizer Violation' => [
                'pim_enrich.normalizer.violation',
                NormalizerInterface::class
            ],
            'Product Builder' => [
                'pim_catalog.builder.product',
                ProductBuilderInterface::class
            ],
            // Only used for product models
            'Product Model Repository' => [
                'pim_catalog.repository.product_model',
                ProductModelRepositoryInterface::class
            ],
            'Product Model Factory' => [
                'pim_catalog.factory.product_model',
                SimpleFactoryInterface::class
            ],
            'Product Model Updater' => [
                'pim_catalog.updater.product_model',
                ObjectUpdaterInterface::class
            ],
            'Product Model Saver' => [
                'pim_catalog.saver.product_model',
                SaverInterface::class
            ],
            // Only used for products
            'Product Repository' => [
                'pim_catalog.repository.product',
                ProductRepositoryInterface::class
            ],
            'Product Updater' => [
                'pim_catalog.updater.product',
                ObjectUpdaterInterface::class
            ],
            'Product Saver' => [
                'pim_catalog.saver.product',
                SaverInterface::class
            ],
            'User Context' => [
                'pim_user.context.user',
                UserContext::class
            ],
            'Localizer Converter' => [
                'pim_catalog.localization.localizer.converter',
                AttributeConverterInterface::class
            ],
            'Comparator Product Filter ' => [
                'pim_catalog.comparator.filter.product',
                FilterInterface::class
            ],
            'Enrich to Standard product Value Converter' => [
                'pim_enrich.converter.enrich_to_standard.product_value',
                ConverterInterface::class
            ],

        ];
    }
}
