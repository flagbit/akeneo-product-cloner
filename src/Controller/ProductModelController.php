<?php

namespace Flagbit\Bundle\ProductClonerBundle\Controller;

use Akeneo\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Pim\Component\Catalog\Repository\ProductModelRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductModelController extends Controller
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
     * DefaultController constructor.
     *
     * @param ProductModelRepositoryInterface $productModelRepository
     * @param NormalizerInterface             $normalizer
     * @param SimpleFactoryInterface          $productModelFactory
     * @param ObjectUpdaterInterface          $productModelUpdater
     * @param SaverInterface                  $productModelSaver
     * @param ValidatorInterface              $validator
     * @param NormalizerInterface             $violiationNormalizer
     */
    public function __construct(
        ProductModelRepositoryInterface $productModelRepository,
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

        // check 'code_to_clone' is provided otherwise HTTP bad request
        if (false === isset($content['code_to_clone'])) {
            return new JsonResponse('Field "code_to_clone" is missing.', Response::HTTP_BAD_REQUEST);
        }

        // check whether product to be cloned is found otherwise not found HTTP
        $productModel = $this->productModelRepository->findOneByIdentifier($content['code_to_clone']);
        if (null === $productModel) {
            return new JsonResponse(
                sprintf('Product model with code %s could not be found.', $content['code_to_clone']),
                Response::HTTP_NOT_FOUND
            );
        }

        // create a new product model
        $cloneProductModel = $this->productModelFactory->create();

        // clone product using Akeneo normalizer and updater for reusing code
        $normalizedProduct = $this->normalizer->normalize($productModel, 'standard');
        $this->productModelUpdater->update($cloneProductModel, $normalizedProduct);
        // set the new product model identifier 'code'
        $cloneProductModel->setCode(isset($content['code']) ? $content['code'] : '');

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
    }
}
