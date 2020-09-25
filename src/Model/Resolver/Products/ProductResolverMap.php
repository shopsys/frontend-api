<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Resolver\Products;

use BadMethodCallException;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductCollectionFacade;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;

class ProductResolverMap extends ResolverMap
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Collection\ProductCollectionFacade
     */
    protected $productCollectionFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade
     */
    protected $flagFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\CategoryFacade
     */
    protected $categoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade|null
     */
    protected $brandFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\Collection\ProductCollectionFacade $productCollectionFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade $flagFacade
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade|null $brandFacade
     */
    public function __construct(
        Domain $domain,
        ProductCollectionFacade $productCollectionFacade,
        FlagFacade $flagFacade,
        CategoryFacade $categoryFacade,
        ?BrandFacade $brandFacade = null
    ) {
        $this->domain = $domain;
        $this->productCollectionFacade = $productCollectionFacade;
        $this->flagFacade = $flagFacade;
        $this->categoryFacade = $categoryFacade;
        $this->brandFacade = $brandFacade;
    }

    /**
     * @required
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade $brandFacade
     * @internal This function will be replaced by constructor injection in next major
     */
    public function setBrandFacade(BrandFacade $brandFacade): void
    {
        if ($this->brandFacade !== null && $this->brandFacade !== $brandFacade) {
            throw new BadMethodCallException(
                sprintf('Method "%s" has been already called and cannot be called multiple times.', __METHOD__)
            );
        }
        if ($this->brandFacade !== null) {
            return;
        }

        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in the next major. Use the constructor injection instead.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );
        $this->brandFacade = $brandFacade;
    }

    /**
     * @return array
     */
    protected function map(): array
    {
        return [
            'Product' => [
                self::RESOLVE_TYPE => function ($data) {
                    $isMainVariant = $data instanceof Product ? $data->isMainVariant() : $data['is_main_variant'];
                    $isVariant = $data instanceof Product ? $data->isVariant() : $data['main_variant_id'] !== null;

                    if ($isMainVariant) {
                        return 'MainVariant';
                    }

                    if ($isVariant) {
                        return 'Variant';
                    }
                    return 'RegularProduct';
                },
            ],
            'RegularProduct' => $this->mapProduct(),
            'Variant' => $this->mapProduct(),
            'MainVariant' => $this->mapProduct(),
        ];
    }

    /**
     * @return array
     */
    protected function mapProduct(): array
    {
        return [
            'shortDescription' => function ($data) {
                return $data instanceof Product ? $data->getShortDescription(
                    $this->domain->getId()
                ) : $data['short_description'];
            },
            'link' => function ($data) {
                $productId = $data instanceof Product ? $data->getId() : $data['id'];
                return $this->getProductLink($productId);
            },
            'categories' => function ($data) {
                return $data instanceof Product ? $data->getCategoriesIndexedByDomainId()[$this->domain->getId()] : $this->getCategoriesForData(
                    $data
                );
            },
            'flags' => function ($data) {
                return $this->getFlagsForData($data);
            },
            'availability' => function ($data) {
                return $data instanceof Product ? $data->getCalculatedAvailability() : ['name' => $data['availability']];
            },
            'unit' => function ($data) {
                return $data instanceof Product ? $data->getUnit() : ['name' => $data['unit']];
            },
            'stockQuantity' => function ($data) {
                return $data instanceof Product ? $data->getStockQuantity() : $data['stock_quantity'];
            },
            'isUsingStock' => function ($data) {
                return $data instanceof Product ? $data->isUsingStock() : $data['is_using_stock'];
            },
            'brand' => function ($data) {
                if ($data instanceof Product) {
                    return $data->getBrand();
                }

                if ((int)$data['brand'] > 0) {
                    return $this->brandFacade->getById((int)$data['brand']);
                }

                return null;
            },
        ];
    }

    /**
     * @param int $productId
     * @return string
     */
    protected function getProductLink(int $productId): string
    {
        $absoluteUrlsIndexedByProductId = $this->productCollectionFacade->getAbsoluteUrlsIndexedByProductId(
            [$productId],
            $this->domain->getCurrentDomainConfig()
        );

        return $absoluteUrlsIndexedByProductId[$productId];
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product|array $data
     * @return \Shopsys\FrameworkBundle\Model\Product\Flag\Flag[]
     */
    protected function getFlagsForData($data): array
    {
        if ($data instanceof Product) {
            return $data->getFlags();
        }
        $flags = [];
        foreach ($data['flags'] as $flagId) {
            $flags[] = $this->flagFacade->getById($flagId);
        }
        return $flags;
    }

    /**
     * @param array $data
     * @return \Shopsys\FrameworkBundle\Model\Category\Category[]
     */
    protected function getCategoriesForData($data): array
    {
        $categoryIds = $data['categories'];

        $categories = [];
        foreach ($categoryIds as $categoryId) {
            $categories[] = $this->categoryFacade->getById($categoryId);
        }
        return $categories;
    }
}
