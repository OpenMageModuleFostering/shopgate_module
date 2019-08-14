<?php
/**
 * Shopgate GmbH
 *
 * URHEBERRECHTSHINWEIS
 *
 * Dieses Plugin ist urheberrechtlich geschützt. Es darf ausschließlich von Kunden der Shopgate GmbH
 * zum Zwecke der eigenen Kommunikation zwischen dem IT-System des Kunden mit dem IT-System der
 * Shopgate GmbH über www.shopgate.com verwendet werden. Eine darüber hinausgehende Vervielfältigung, Verbreitung,
 * öffentliche Zugänglichmachung, Bearbeitung oder Weitergabe an Dritte ist nur mit unserer vorherigen
 * schriftlichen Zustimmung zulässig. Die Regelungen der §§ 69 d Abs. 2, 3 und 69 e UrhG bleiben hiervon unberührt.
 *
 * COPYRIGHT NOTICE
 *
 * This plugin is the subject of copyright protection. It is only for the use of Shopgate GmbH customers,
 * for the purpose of facilitating communication between the IT system of the customer and the IT system
 * of Shopgate GmbH via www.shopgate.com. Any reproduction, dissemination, public propagation, processing or
 * transfer to third parties is only permitted where we previously consented thereto in writing. The provisions
 * of paragraph 69 d, sub-paragraphs 2, 3 and paragraph 69, sub-paragraph e of the German Copyright Act shall remain unaffected.
 *
 * @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * User: pliebig
 * Date: 10.03.14
 * Time: 17:21
 * E-Mail: p.liebig@me.com
 */

/**
 * @package
 * @author      Peter Liebig <p.liebig@me.com>
 */
class Shopgate_Framework_Model_Export_Product extends Shopgate_Framework_Model_Export_Abstract
{

    /**
     * @var null
     */
    protected $_giftWrappingLabel = null;

    /**
     * for item export.
     * gift wrap and gift message
     *
     * @var array
     */
    protected $_giftWrappingOptions = array();

    /**
     * get manufacturer suggested retail price
     *
     * @param $product
     *
     * @return float
     */
    public function getMsrp($product)
    {
        $msrp = $product->getMsrp();
        if ($msrp > 0) {
            $msrp = $this->_getExportHelper()->convertPriceCurrency($msrp);
            $msrp = round($msrp, 2);
            $msrp = number_format($msrp, 2, ".", "");
        } else {
            $msrp = null;
        }
        return $msrp;
    }

    /**
     * getting images as array
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Catalog_Model_Product $parentItem
     *
     * @return array
     */
    public function getImages($product, $parentItem = null)
    {
        $images = $this->_getProductImages($product);

        if ($parentItem) {
            $parentImages = $this->_getProductImages($parentItem);
            switch (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_VARIATION_IMAGE)) {
                case 1:
                    $images = $parentImages;
                    break;
                case 2:
                    $images = array_merge($parentImages, $images);
                    break;
                case 3:
                    $images = array_merge($images, $parentImages);
                    break;
            }
        }

        $images = array_unique($images);
        foreach ($images as &$image) {
            $image = ($image);
        }
        return $images;
    }


    /**
     * get product images
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    protected function _getProductImages($product)
    {
        $images = array();
        if ($product) {
            $mediaGallery = $this->_getExportHelper()->getMediaImages($product);
            if (!empty($mediaGallery)) {
                foreach ($mediaGallery as $image) {
                    if ($image->getFile()) {
                        $images[] = $image->geturl();
                    }
                }
            }
        }

        return array_unique($images);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return mixed
     */
    public function getWeight($product)
    {
        return $product->getWeight() * $this->_getExportHelper()->getWeightFactor();
    }

    /**
     * get product name
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getProductName($product)
    {
        return trim($product->getName());
    }

    /**
     * @param $priceType
     *
     * @return null|string
     */
    protected function _getOptionPriceType($priceType)
    {
        $type = null;
        switch ($priceType) {
            case 'percent':
                $type = Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_PERCENT;
                break;
            case 'fixed':
                $type = Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED;
                break;
        }
        return $type;
    }

    /**
     * @param $type
     *
     * @return null|string
     */
    protected function _getInputType($type)
    {
        $input = null;
        switch ($type) {
            case Mage_Catalog_Model_Product_Option::OPTION_TYPE_AREA:
                $input = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_AREA;
                break;
            case Mage_Catalog_Model_Product_Option::OPTION_TYPE_CHECKBOX:
                $input = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX;
                break;
            case Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN:
                $input = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case Mage_Catalog_Model_Product_Option::OPTION_TYPE_FIELD:
                $input = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT;
                break;
            case Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE:
                $input = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_MULTIPLE;
                break;
            case Mage_Catalog_Model_Product_Option::OPTION_TYPE_RADIO:
                $input = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_RADIO;
                break;
            case Mage_Catalog_Model_Product_Option::OPTION_TYPE_FILE:
                $input = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE;
                break;
        }
        return $input;
    }

    /**
     * copy of core format price
     *
     * @param        $price
     * @param int    $digits
     * @param string $decimalPoint
     * @param string $thousandPoints
     *
     * @return float|string
     */
    protected function _formatPriceNumber($price, $digits = 2, $decimalPoint = ".", $thousandPoints = "")
    {
        $price = round($price, $digits);
        $price = number_format($price, $digits, $decimalPoint, $thousandPoints);
        return $price;
    }

    /**
     * Fetches attributes for properties column and filters by ignored attributes.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param                            $ignoredProductAttributeCodes
     * @param array                      $forcedProductAttributeCodes
     *
     * @return array
     */
    protected function _getProductProperties(
        $product, $ignoredProductAttributeCodes, $forcedProductAttributeCodes = array()
    ) {
        $properties = array();

        $cacheKey = 'product_type_' . $product->getTypeId() . '_attributes_' . $product->getAttributeSetId();
        $cache    = Mage::app()->getCacheInstance();
        $value    = $cache->load($cacheKey);

        if ($value !== false) {
            $attributes = unserialize($value);
        } else {
            $attributes = $product->getAttributes();
            $attrCache  = array();
            foreach ($attributes as $attribute) {
                $code = $attribute->getAttributeCode();

                $isFilterable = Mage::getStoreConfigFlag('shopgate/export/filterable_attributes')
                    ? $attribute->getIsFilterable()
                    : false;

                /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
                if ($attribute
                    && ($attribute->getIsVisibleOnFront() || $isFilterable
                        || in_array(
                            $code, $forcedProductAttributeCodes
                        ))
                ) {
                    if (in_array($code, $ignoredProductAttributeCodes)
                        && !in_array(
                            $code, $forcedProductAttributeCodes
                        )
                    ) {
                        continue;
                    }
                    $attrCache[$code] = array(
                        'id'    => $attribute->getId(),
                        'label' => $attribute->getStoreLabel($this->_getConfig()->getStoreViewId())
                    );
                }
            }
            $attributes = $attrCache;
            $cache->save(
                  serialize($attrCache),
                  $cacheKey,
                  array(
                      'shopgate_export',
                      Mage_Core_Model_Mysql4_Collection_Abstract::CACHE_TAG,
                      Mage_Catalog_Model_Resource_Eav_Attribute::CACHE_TAG
                  ),
                  3600
            );
        }

        foreach ($attributes as $code => $data) {
            $value = $product->getResource()->getAttribute($code)->getFrontend()->getValue($product);
            if ($value) {
                $properties[$code] = "{$data['label']}=>{$value}";
            }
        }

        return $properties;
    }

    /**
     * @param Mage_CatalogInventory_Model_Stock_Item $stockItem
     *
     * @return float|int
     */
    protected function _getStackPriceMultiplicator($stockItem)
    {
        $priceMultiplier = 1;
        if ($stockItem->getEnableQtyIncrements()) {
            $stackQuantity = ceil($stockItem->getQtyIncrements());
            if ($stackQuantity > 1) {
                $priceMultiplier = $stackQuantity;
            }
        }
        return $priceMultiplier;
    }

    /**
     * get gift wrapping label
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return null|string
     */
    protected function _getGiftWrappingLabel(Mage_Catalog_Model_Product $product)
    {
        // set this label only once
        if (null === $this->_giftWrappingLabel) {
            $this->_giftWrappingLabel = $this->_getHelper()->__('Gift Wrapping');
            if (empty($this->_giftWrappingLabel)) {
                $this->_giftWrappingLabel = $product->getResource()->getAttribute(
                                                    'gift_wrapping_available'
                )->getStoreLabel();
            }
        }

        return $this->_giftWrappingLabel;
    }

    /**
     * get gift wrapping options for enterprise only
     *
     * @return array
     */
    protected function _getGiftWrappingOptions()
    {
        if (!count($this->_giftWrappingOptions)) {
            $wrappingModel      = Mage::getModel('enterprise_giftwrapping/wrapping');
            $wrappingCollection = $wrappingModel->getCollection();
            foreach ($wrappingCollection as $wrapping) {
                if ($wrapping->getData('status')) {
                    $wrappingModel->load($wrapping->getData('wrapping_id'));
                    $this->_giftWrappingOptions[$wrapping->getData('wrapping_id')] = array(
                        'design' => $wrappingModel->getData('design'),
                        'price'  => $wrappingModel->getData('base_price') * 100
                    );
                }
            }
        }

        return $this->_giftWrappingOptions;
    }
}