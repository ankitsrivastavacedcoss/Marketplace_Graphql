<?php
namespace Ced\CsMarketplaceGraphql\Model\Resolver\Products;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ProductAttributeFilter implements ResolverInterface
{
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\Layer\Category\FilterableAttributeList $filterableAttributes
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\Category\FilterableAttributeList $filterableAttributes,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver
    ) {
        $this->objectManager = $objectManager;
        $this->layerResolver = $layerResolver;
        $this->filterableAttributes = $filterableAttributes;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): ?array {
        if (!isset($args['vendor_id'])) {
            return null;
        }
        $output = [];
        try {
            $output['aggregations'] = $this->retrieve($args['vendor_id']);
            $output['seller_info'] = $this->sellerinfo($args['vendor_id']);
        } catch (\Exception $e) {
        }
        return $output;
    }

    /**
     * @param $vendorId
     * @return array
     */
    public function sellerinfo($vendorId){

        $filterArray['seller_info'] = [
            'public_name' => 'Ankit',
            'shop_url' => 'asdasda',
        ];
        return $filterArray;
    }
    /**
     * @param $vendorId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function retrieve($vendorId): array
    {
        $filterList = $this->objectManager->create(
            \Magento\Catalog\Model\Layer\FilterList::class,
            [
                'filterableAttributes' => $this->filterableAttributes
            ]
        );
        $layer = $this->layerResolver->get();
        $layer = $layer->setCurrentCategory(2);
        $collection = $layer->getProductCollection();
        $vendorTableName = $collection->getTable('ced_csmarketplace_vendor_products');
        $collection->getSelect()->join(
            ['vendorTable' => $vendorTableName],
            "e.entity_id = vendorTable.product_id AND vendorTable.vendor_id = $vendorId",
            []
        );

        $layer->prepareProductCollection($collection);
        $filters = $filterList->getFilters($layer);

        $filterArray = [];
        $i = 0;
        foreach ($filters as $filter) {
            try {
                $availablefilter = (string)$filter->getName();
//                $attribute = $filter->getAttributeModel();
                $items = $filter->getItems();
                $filterValues = [];
                $j = 0;
                foreach ($items as $item) {
                    /*@var $item \Magento\Catalog\Model\Layer\Filter\Item */
                    $filterValues[$j]['label'] = $item->getLabel();
                    $filterValues[$j]['value'] = $item->getValue();
                    $filterValues[$j]['count'] = $item->getCount();
                    $j++;
                }
                if (!empty($filterValues) && count($filterValues)>1) {
//                $filterArray[$availablefilter] =  $filterValues;
                    $filterArray[] = [
                        'attribute_code' => $availablefilter,
                        'label' => $availablefilter,
                        'count' => $j,
                        'options' => $filterValues,
                    ];
                }
                $i++;
            } catch (\Magento\Framework\Exception\LocalizedException $e){
            }
        }
        return $filterArray;
    }
}
