<?php

namespace Flagbit\Bundle\ProductClonerBundle\Controller;


use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Pim\Bundle\UserBundle\Context\UserContext;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Component\Catalog\Builder\ProductBuilderInterface;
use Pim\Component\Catalog\Comparator\Filter\FilterInterface;
use Pim\Component\Catalog\Localization\Localizer\AttributeConverterInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Repository\ProductRepositoryInterface;
use Pim\Component\Enrich\Converter\ConverterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends Controller
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
        ProductBuilderInterface $variantProductBuilder
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

        // check 'code_to_clone' is provided otherwise HTTP bad request
        if (false === isset($data['code_to_clone'])) {
            return new JsonResponse('Field "code_to_clone" is missing.', Response::HTTP_BAD_REQUEST);
        }

        // check whether product to be cloned is found otherwise not found HTTP
        $productModel = $this->productRepository->findOneByIdentifier($data['code_to_clone']);
        if (null === $productModel) {
            return new JsonResponse(
                sprintf('Product model with code %s could not be found.', $data['code_to_clone']),
                Response::HTTP_NOT_FOUND
            );
        }
        unset($data['code_to_clone']);
        if (isset($data['parent'])) {
            $cloneProduct = $this->variantProductBuilder->createProduct();
        } else {
            $cloneProduct = $this->productBuilder->createProduct(
                $data['identifier']
            );
        }

        // clone product using Akeneo normalizer
        $normalizedProduct = $this->normalizer->normalize($productModel, 'standard');

        $normalizedProduct = $this->removeIdentifierAttributeValue($normalizedProduct);
        $this->productUpdater->update($cloneProduct, $normalizedProduct);
        if (isset($data['values'])) {
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

        return new JsonResponse();
    }

    private function removeIdentifierAttributeValue(array $data): array
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
            'locale' => $this->userContext->getUiLocale()->getCode()
        ]);

        $dataFiltered = $this->emptyValuesFilter->filter($product, ['values' => $values]);

        if (!empty($dataFiltered)) {
            $data = array_replace($data, $dataFiltered);
        } else {
            $data['values'] = [];
        }

        $this->productUpdater->update($product, $data);
    }
}
