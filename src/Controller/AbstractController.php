<?php

namespace Flagbit\Bundle\ProductClonerBundle\Controller;

use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithFamilyVariantInterface;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class AbstractController extends Controller
{

    abstract protected function getNormalizer(): NormalizerInterface;
    abstract protected function getAttributeRepository(): AttributeRepositoryInterface;

    protected function normalizeProduct(EntityWithFamilyVariantInterface $product)
    {
        $normalizedProduct = $this->getNormalizer()->normalize($product, 'standard');

        while ($parent = $product->getParent()) {
            foreach ($parent->getValuesForVariation() as $value) {
                //this workaround removes the attributes of all parent models, as the getValues() Method,
                // which is called by the normalizer, returns all Values including the values of the parent Model
                unset($normalizedProduct['values'][$value->getAttribute()->getCode()]);
            }
            $product = $parent;
        };

        foreach ($this->getAttributeRepository()->findUniqueAttributeCodes() as $attributeCode) {
            unset($normalizedProduct['values'][$attributeCode]);
        }
        unset($normalizedProduct['identifier']);
        return $normalizedProduct;
    }
}
