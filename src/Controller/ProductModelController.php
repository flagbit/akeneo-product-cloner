<?php

namespace Flagbit\Bundle\ProductClonerBundle\Controller;

use Akeneo\Tool\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Tool\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductModelRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductModelController extends AbstractController
{
    /**
     * @var ProductModelRepositoryInterface
     */
    private $productModelRepository;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var SimpleFactoryInterface
     */
    private $productModelFactory;

    /**
     * @var ObjectUpdaterInterface
     */
    private $productModelUpdater;

    /**
     * @var SaverInterface
     */
    private $productModelSaver;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var NormalizerInterface
     */
    private $violationNormalizer;
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * DefaultController constructor.
     *
     * @param ProductModelRepositoryInterface $productModelRepository
     * @param NormalizerInterface $normalizer
     * @param SimpleFactoryInterface $productModelFactory
     * @param ObjectUpdaterInterface $productModelUpdater
     * @param SaverInterface $productModelSaver
     * @param ValidatorInterface $validator
     * @param NormalizerInterface $violiationNormalizer
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        ProductModelRepositoryInterface $productModelRepository,
        AttributeRepositoryInterface $attributeRepository,
        NormalizerInterface $normalizer,
        SimpleFactoryInterface $productModelFactory,
        ObjectUpdaterInterface $productModelUpdater,
        SaverInterface $productModelSaver,
        ValidatorInterface $validator,
        NormalizerInterface $violiationNormalizer
    ) {
        $this->productModelRepository = $productModelRepository;
        $this->normalizer = $normalizer;
        $this->productModelFactory = $productModelFactory;
        $this->productModelUpdater = $productModelUpdater;
        $this->productModelSaver = $productModelSaver;
        $this->validator = $validator;
        $this->violationNormalizer = $violiationNormalizer;
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
        $content = json_decode($request->getContent(), true);

        try {
            // check 'code_to_clone' is provided otherwise HTTP bad request
        if (false === isset($content['code_to_clone'])) {
            return new JsonResponse(['values' => [['message' => 'Field "code_to_clone" is missing.']]], Response::HTTP_BAD_REQUEST);
        }

        // check 'code' is provided otherwise HTTP bad request
        if (false === isset($content['code'])) {
            return new JsonResponse(['values' => [['message' => 'Failed "Code" is missing.']]], Response::HTTP_BAD_REQUEST);
        }

        // check whether product to be cloned is found otherwise not found HTTP
        $productModel = $this->productModelRepository->findOneByIdentifier($content['code_to_clone']);
        if (null === $productModel) {
            return new JsonResponse(
                ['values' => [['message' => sprintf('Product model with code %s could not be found.', $content['code_to_clone'])]]],
                Response::HTTP_NOT_FOUND
            );
        }
        unset($content['code_to_clone']);
        // create a new product model
        $cloneProductModel = $this->productModelFactory->create();

        // clone product using Akeneo normalizer and updater for reusing code
        $normalizedProduct = $this->normalizeProduct($productModel);
        $this->productModelUpdater->update($cloneProductModel, $normalizedProduct);
        $this->productModelUpdater->update($cloneProductModel, $content);
        $cloneProductModel->setCode($content['code']);
        // validate product model clone and return violations if found
        $violations = $this->validator->validate($cloneProductModel);
        if (count($violations) > 0) {
            $normalizedViolations = [];
            foreach ($violations as $violation) {
                $violation = $this->violationNormalizer->normalize(
                    $violation,
                    'internal_api',
                    ['product_model' => $cloneProductModel]
                );
                $normalizedViolations[] = $violation;
            }

            return new JsonResponse(['values' => $normalizedViolations], Response::HTTP_BAD_REQUEST);
        }

        $this->productModelSaver->save($cloneProductModel);

        return new JsonResponse();

        } catch (\Exception $e) {
            return new JsonResponse(['values' => [['message' => $e->getMessage()]]], Response::HTTP_BAD_REQUEST);
        }
    }

    protected function getNormalizer(): NormalizerInterface
    {
        return $this->normalizer;
    }

    protected function getAttributeRepository(): AttributeRepositoryInterface
    {
        return $this->attributeRepository;
    }
}
