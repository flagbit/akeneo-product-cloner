<?php

namespace Flagbit\Bundle\ProductClonerBundle\Controller;

use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Tool\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Akeneo\UserManagement\Bundle\Context\UserContext;
use Akeneo\Pim\Enrichment\Component\Product\Builder\ProductBuilderInterface;
use Akeneo\Pim\Enrichment\Component\Product\Comparator\Filter\FilterInterface;
use Akeneo\Pim\Enrichment\Component\Product\Localization\Localizer\AttributeConverterInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductRepositoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Converter\ConverterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ObjectUpdaterInterface
     */
    private $productUpdater;

    /**
     * @var SaverInterface
     */
    private $productSaver;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var UserContext
     */
    private $userContext;

    /**
     * @var ProductBuilderInterface
     */
    private $productBuilder;

    /**
     * @var AttributeConverterInterface
     */
    private $localizedConverter;

    /**
     * @var FilterInterface
     */
    private $emptyValuesFilter;

    /**
     * @var ConverterInterface
     */
    private $productValueConverter;

    /**
     * @var NormalizerInterface
     */
    private $constraintViolationNormalizer;

    /**
     * @var ProductBuilderInterface
     */
    private $variantProductBuilder;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var string[]
     */
    private $attributeCodeBlacklist;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        AttributeRepositoryInterface $attributeRepository,
        ObjectUpdaterInterface $productUpdater,
        SaverInterface $productSaver,
        NormalizerInterface $normalizer,
        ValidatorInterface $validator,
        UserContext $userContext,
        ProductBuilderInterface $productBuilder,
        AttributeConverterInterface $localizedConverter,
        FilterInterface $emptyValuesFilter,
        ConverterInterface $productValueConverter,
        NormalizerInterface $constraintViolationNormalizer,
        ProductBuilderInterface $variantProductBuilder,
        array $attributeCodeBlacklist
    ) {

        $this->productRepository = $productRepository;
        $this->productUpdater = $productUpdater;
        $this->productSaver = $productSaver;
        $this->normalizer = $normalizer;
        $this->validator = $validator;
        $this->userContext = $userContext;
        $this->productBuilder = $productBuilder;
        $this->localizedConverter = $localizedConverter;
        $this->emptyValuesFilter = $emptyValuesFilter;
        $this->productValueConverter = $productValueConverter;
        $this->constraintViolationNormalizer = $constraintViolationNormalizer;
        $this->variantProductBuilder = $variantProductBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->attributeCodeBlacklist = $attributeCodeBlacklist;
    }

    /**
     * @param Request $request
     *
     * @AclAncestor("pim_enrich_product_model_create")
     *
     * @return JsonResponse
     */
    public function cloneAction(Request $request) : JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        try {
            // check 'code_to_clone' is provided otherwise HTTP bad request
            if (false === isset($data['code_to_clone'])) {
                $message = [['message' => 'Field "code_to_clone" is missing.']];
                return new JsonResponse(['values' => $message], Response::HTTP_BAD_REQUEST);
            }
            // check whether product to be cloned is found otherwise not found HTTP
            $product = $this->productRepository->findOneByIdentifier($data['code_to_clone']);
            if (null === $product) {
                $message = [['message' => sprintf(
                    'Product model with code %s could not be found.',
                    $data['code_to_clone']
                )]];
                return new JsonResponse(
                    ['values' => $message],
                    Response::HTTP_NOT_FOUND
                );
            }
            unset($data['code_to_clone']);
            if (isset($data['parent'])) {
                // TODO: remove this as soon as support of 2.1 is dropped
                $cloneProduct = $this->variantProductBuilder->createProduct();
            } else {
                // check 'code' is provided otherwise HTTP bad request
                if (false === isset($data['code'])) {
                    $message = [['message' => 'Failed "Code" is missing.']];
                    return new JsonResponse(['values' => $message], Response::HTTP_BAD_REQUEST);
                }

                $cloneProduct = $this->productBuilder->createProduct(
                    $data['code']
                );
                unset($data['code']);
            }

            // clone product using Akeneo normalizer
            $normalizedProduct = $this->normalizeProduct($product);

            $normalizedProduct = $this->removeIdentifierAttributeValue($normalizedProduct);
            $this->productUpdater->update($cloneProduct, $normalizedProduct);
            if (!empty($data['values'])) {
                $this->updateProduct($cloneProduct, $data);
            }
            // validate product model clone and return violations if found
            $violations = $this->validator->validate($cloneProduct);
            if (count($violations) > 0) {
                $normalizedViolations = [];
                foreach ($violations as $violation) {
                    $violation = $this->constraintViolationNormalizer->normalize(
                        $violation,
                        'internal_api',
                        ['product' => $cloneProduct]
                    );
                    $normalizedViolations[] = $violation;
                }

                return new JsonResponse(['values' => $normalizedViolations], Response::HTTP_BAD_REQUEST);
            }
            $this->productSaver->save($cloneProduct);
            return new JsonResponse('Success.');
        } catch (\Exception $e) {
            return new JsonResponse(['values' => [['message' => 'Failed.']]], $e->getMessage());
        }
    }

    private function removeIdentifierAttributeValue(array $data) : array
    {
        unset($data['identifier']);
        $identifierAttributeCode = $this->attributeRepository->getIdentifier()->getCode();

        if (isset($data['values'][$identifierAttributeCode])) {
            unset($data['values'][$identifierAttributeCode]);
        }
        return $data;
    }

    /**
     * Updates product with the provided request data
     *
     * @param ProductInterface $product
     * @param array            $data
     */
    private function updateProduct(ProductInterface $product, array $data)
    {
        $values = $this->productValueConverter->convert($data['values']);

        $values = $this->localizedConverter->convertToDefaultFormats($values, [
            'locale' => $this->userContext->getUiLocale()->getCode(),
        ]);

        $dataFiltered = $this->emptyValuesFilter->filter($product, ['values' => $values]);

        if (!empty($dataFiltered)) {
            $data = array_replace($data, $dataFiltered);
        } else {
            $data['values'] = [];
        }

        $this->productUpdater->update($product, $data);
    }

    /**
     * @return string[]
     */
    protected function getAttributeCodeBlacklist() : array
    {
        return $this->attributeCodeBlacklist;
    }

    protected function getNormalizer() : NormalizerInterface
    {
        return $this->normalizer;
    }

    protected function getAttributeRepository() : AttributeRepositoryInterface
    {
        return $this->attributeRepository;
    }
}
