<?php
/**
 * Shopgate GmbH
 * URHEBERRECHTSHINWEIS
 * Dieses Plugin ist urheberrechtlich geschützt. Es darf ausschließlich von Kunden der Shopgate GmbH
 * zum Zwecke der eigenen Kommunikation zwischen dem IT-System des Kunden mit dem IT-System der
 * Shopgate GmbH über www.shopgate.com verwendet werden. Eine darüber hinausgehende Vervielfältigung, Verbreitung,
 * öffentliche Zugänglichmachung, Bearbeitung oder Weitergabe an Dritte ist nur mit unserer vorherigen
 * schriftlichen Zustimmung zulässig. Die Regelungen der §§ 69 d Abs. 2, 3 und 69 e UrhG bleiben hiervon unberührt.
 * COPYRIGHT NOTICE
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
 * Time: 10:20
 * E-Mail: p.liebig@me.com
 */

/**
 * xml export model for products
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Export_Product_Xml
    extends Shopgate_Model_Catalog_Product
{
    /**
     * default tax class id none
     */
    const DEFAULT_TAX_CLASS_ID_NONE = 0;

    /**
     * @var Mage_Catalog_Model_Product $item
     */
    protected $item;

    /**
     * @var Mage_Catalog_Model_Product $_parent
     */
    protected $_parent = null;

    /**
     * @var array
     */
    protected $fireMethods
        = array(
            'setLastUpdate',
            'setUid',
            'setName',
            'setTaxPercent',
            'setTaxClass',
            'setCurrency',
            'setDescription',
            'setDeeplink',
            'setPromotionSortOrder',
            'setInternalOrderInfo',
            'setAgeRating',
            'setWeight',
            'setWeightUnit',
            'setPrice',
            'setShipping',
            'setManufacturer',
            'setVisibility',
            'setStock',
            'setImages',
            'setCategoryPaths',
            'setProperties',
            'setIdentifiers',
            'setTags',
            'setRelations',
            'setAttributeGroups',
            'setInputs',
            'setChildren',
            'setDisplayType'
        );

    /**
     * @var array
     */
    protected $_ignoredProductAttributeCodes = array();

    /**
     * @var array
     */
    protected $_forcedProductAttributeCodes = array();

    /**
     * @var null
     */
    protected $_eanAttributeCode = null;

    /**
     * const for config path to include price with tax
     */
    const CONFIG_XML_PATH_PRICE_INCLUDES_TAX = 'tax/calculation/price_includes_tax';

    /**
     * set parent to null;
     */
    public function __construct()
    {
        parent::__construct();
        $this->_parent = null;
    }

    /**
     * @return Shopgate_Framework_Helper_Export
     */
    protected function _getExportHelper()
    {
        return Mage::helper('shopgate/export');
    }

    /**
     * @return Shopgate_Framework_Model_Config
     */
    protected function _getConfig()
    {
        return $this->_getHelper()->getConfig();
    }

    /**
     * return customer helper
     *
     * @return Shopgate_Framework_Helper_Customer
     */
    protected function _getCustomerHelper()
    {
        return Mage::helper('shopgate/customer');
    }

    /**
     * return default data helper
     *
     * @return Shopgate_Framework_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('shopgate');
    }

    /**
     * return config helper
     *
     * @return Shopgate_Framework_Helper_Config
     */
    protected function _getConfigHelper()
    {
        return Mage::helper('shopgate/config');
    }

    /**
     * set last updated update date
     */
    public function setLastUpdate()
    {
        parent::setLastUpdate(date(DateTime::ISO8601, strtotime($this->item->getUpdatedAt())));
    }

    /**
     * set unique ID
     */
    public function setUid()
    {
        parent::setUid($this->item->getId());
    }

    /**
     * set name
     */
    public function setName()
    {
        $parentName = Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_PARENT_PRODUCT_NAME);

        if ($parentName && $this->_parent != null) {
            $name = $this->_parent->getName();
        } else {
            $name = $this->item->getName();
        }
        parent::setName($name);
    }

    /**
     * @param $product
     *
     * @return float
     */
    protected function _getTaxRate($product)
    {
        $request = new Varien_Object(
            array(
                'country_id'        => Mage::getStoreConfig(
                                           "tax/defaults/country",
                                           $this->_getConfig()->getStoreViewId()
                    ),
                'customer_class_id' => Mage::getModel("tax/calculation")->getDefaultCustomerTaxClass(
                                           $this->_getConfig()->getStoreViewId()
                    ),
                'product_class_id'  => $product->getTaxClassId()
            )
        );

        /** @var Mage_Tax_Model_Calculation $model */
        $model = Mage::getSingleton('tax/calculation');

        return $model->getRate($request);
    }

    /**
     * set tax percentage
     */
    public function setTaxPercent()
    {
	    if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
		    && $this->item->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC
	    ) {
		    $childIds       = $this->item->getTypeInstance()->getChildrenIds($this->item->getId());
		    $taxRates       = array();
		    foreach ($childIds as $childOption) {
			    foreach ($childOption as $childId) {
				    $product = Mage::getModel('catalog/product')
					    ->setStoreId($this->_getConfig()->getStoreViewId())
					    ->load($childId);

				    $taxRates[] = $this->_getTaxRate($product);
			    }
		    }

		    parent::setTaxPercent(max($taxRates));
	    } else {
            if(!($this->item->getTaxClassId() == Shopgate_Framework_Model_Export_Product_Xml::DEFAULT_TAX_CLASS_ID_NONE && $this->_parent)) {
                parent::setTaxPercent($this->_getTaxRate($this->item));
            }
	    }
    }

    /**
     * set tax class
     */
    public function setTaxClass()
    {
	    if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
		    && $this->item->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC
	    ) {
		    $childIds       = $this->item->getTypeInstance()->getChildrenIds($this->item->getId());
		    $taxRates       = array();
		    foreach ($childIds as $childOption) {
			    foreach ($childOption as $childId) {
				    $product = Mage::getModel('catalog/product')
					    ->setStoreId($this->_getConfig()->getStoreViewId())
					    ->load($childId);

				    $taxClassId             = $product->getTaxClassId();
				    $taxRates[$taxClassId]  = $this->_getTaxRate($product);
			    }
		    }

		    parent::setTaxClass(array_search(max($taxRates), $taxRates));
	    } else {
            if(!($this->item->getTaxClassId() == Shopgate_Framework_Model_Export_Product_Xml::DEFAULT_TAX_CLASS_ID_NONE && $this->_parent)) {
                parent::setTaxClass($this->item->getTaxClassId());
            }
	    }
    }

    /**
     * set currency
     */
    public function setCurrency()
    {
        parent::setCurrency(Mage::app()->getStore()->getCurrentCurrencyCode());
    }


    /**
     * set description
     */
    public function setDescription()
    {
        if (Mage::getStoreConfig(
                Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_VARIATION_DESCRIPTION) == 1 && $this->_parent) {
            $description = "";
        } else {
            $description = $this->_getExportHelper()->createFullDescription($this->item, $this->_parent);
        }
        parent::setDescription($description);
    }

    /**
     * set deep link
     */
    public function setDeeplink()
    {
        parent::setDeeplink($this->_getExportHelper()->getDeepLink($this->item, $this->_parent));
    }

    /**
     * set promotion sort order
     */
    public function setPromotionSortOrder()
    {
        //ToDo implement promotion logic in Magento
        parent::setPromotionSortOrder(false);
    }

    /**
     * set internal order info
     */
    public function setInternalOrderInfo()
    {
        $internalOrderInfo = array(
            "store_view_id" => $this->item->getStoreId(),
            "product_id"    => $this->item->getId(),
            "item_type"     => $this->item->getTypeId(),
            "exchange_rate" => $this->_getExportHelper()->convertPriceCurrency(1),
        );

        parent::setInternalOrderInfo($this->_getConfig()->jsonEncode($internalOrderInfo));
    }

    /**
     * set age rating
     */
    public function setAgeRating()
    {
        parent::setAgeRating(false);
    }

    /**
     * set weight
     */
    public function setWeight()
    {
        parent::setWeight(floatval(str_replace(',', '.', $this->item->getWeight())));
    }

    /**
     * set weight unit
     */
    public function setWeightUnit()
    {
        $weightUnit = Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_WEIGHT_UNIT);
        switch ($weightUnit) {
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_KG;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_KG;
                break;
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_GRAMM;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_GRAMM;
                break;
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_AUTO;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_DEFAULT;
                break;
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_POUND;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_POUND;
                break;
            case Shopgate_Framework_Model_System_Config_Source_Weight_Units::WEIGHT_UNIT_OUNCE;
                $weightUnit = Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_OUNCE;
                break;
        }

        parent::setWeightUnit($weightUnit);
    }

    /**
     * set price
     */
    public function setPrice()
    {
        $useParent  = false;

        if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_USE_ROOT_PRICES)
            && $this->_parent != null && $this->_parent->isConfigurable()
        ) {
            $useParent = true;
        }

        $price = $useParent
            ? $this->_parent->getPrice()
            : $this->item->getPrice();

        if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_USE_PRICE_INDEX_ON_EXPORT)) {

            $index = Mage::getModel('catalog/product_indexer_price');
            if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                $finalPrice = $useParent
                    ? $index->load($this->_parent->getId())->getMinPrice()
                    : $index->load($this->item->getId())->getMinPrice();
            } else {
                $finalPrice = $useParent
                    ? $index->load($this->_parent->getId())->getFinalPrice()
                    : $index->load($this->item->getId())->getFinalPrice();
            }
        } else {

            $rulePrice = Mage::helper("shopgate/export")->calcProductPriceRule(
                $useParent
                    ? $this->_parent
                    : $this->item
            );

            $finalPrice = $useParent
                ? $this->_parent->getFinalPrice()
                : $this->item->getFinalPrice();

            if ($rulePrice && $rulePrice < $finalPrice) {
                $finalPrice = $rulePrice;
            }
        }

        if ($finalPrice <= 0) {
            $rulePrice  = Mage::helper("shopgate/export")->calcProductPriceRule($this->item);
            $finalPrice = $this->item->getFinalPrice();
            if ($rulePrice && $rulePrice < $finalPrice) {
                $finalPrice = $rulePrice;
            }
        }

        if (null != $this->_parent
            && $this->_parent->isConfigurable()
            && $useParent
        ) {
            $totalOffset     = 0;
            $totalPercentage = 0;
            $superAttributes = $this->_parent
                ->getTypeInstance(true)
                ->getConfigurableAttributes($this->_parent);

            foreach ($superAttributes as $superAttribute) {
                $code  = $superAttribute->getProductAttribute()->getAttributeCode();
                $index = $this->item->getData($code);

                if ($superAttribute->hasData('prices')) {
                    foreach ($superAttribute->getPrices() as $saPrice) {
                        if ($index == $saPrice["value_index"]) {
                            if ($saPrice["is_percent"]) {
                                $totalPercentage += $saPrice["pricing_value"];
                            } else {
                                $totalOffset += $saPrice["pricing_value"];
                            }
                            break;
                        }
                    }
                }
            }

            if ($price == $this->_parent->getPrice()) {

                $additionaPrice = $price * $totalPercentage / 100;
                $additionaPrice += $totalOffset;

                $price      += $additionaPrice;
                $finalPrice += $additionaPrice;
            }
        }

        $priceModel = new Shopgate_Model_Catalog_Price();

        if (Mage::getConfig()->getModuleConfig('DerModPro_BasePrice')->is('active', 'true')
            && Mage::getStoreConfig('catalog/baseprice/disable_ext') == 0
        ) {
            $format    = "{{baseprice}} / {{reference_amount}} {{reference_unit_short}}";
            $basePrice = Mage::helper("baseprice")->getBasePriceLabel($this->item, $format);
            $basePrice = strip_tags($basePrice);
            $basePrice = htmlentities($basePrice, null, "UTF-8");
            $priceModel->setBasePrice($basePrice);
        }

        $isGross = Mage::getStoreConfig(
                       self::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
                       $this->_getConfig()->getStoreViewId()
        );

        if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
            && $this->item->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC
        ) {
            $minimalPrice = Mage::helper('shopgate/config')->getIsMagentoVersionLower15()
                ? Mage::getModel('bundle/product_price')->getPrices($this->item, 'min')
                : Mage::getModel('bundle/product_price')->getPricesDependingOnTax($this->item, 'min', (bool)$isGross);

            $price = $minimalPrice;
        }

        $priceModel->setPrice($this->_formatPrice($price));
        $priceModel->setCost($this->_formatPrice($this->item->getCost()));
        $priceModel->setSalePrice($this->_formatPrice($finalPrice));
        $priceModel->setMsrp($this->_formatPrice($this->item->getMsrp()));


        if ($isGross) {
            $priceModel->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS);
        } else {
            $priceModel->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET);
        }
        $this->_createTierPriceNode($priceModel);
        $this->_createGroupPriceNode($priceModel);

        parent::setPrice($priceModel);
    }

    /**
     * @param $price Shopgate_Model_Catalog_Price
     */
    protected function _createTierPriceNode(&$price) 
    {
        foreach ($this->item->getData('tier_price') as $tier) {
            if (
                ($tier['website_id'] == Mage::app()->getStore()->getWebsiteId() || $tier['website_id'] == 0)
                && $price->getSalePrice() > $tier['website_price']
            ) {
                $tierPrice = new Shopgate_Model_Catalog_TierPrice();

                $tierPrice->setFromQuantity($tier['price_qty']);
                $tierPrice->setReduction($price->getSalePrice() - $tier['website_price']);
                $tierPrice->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);
                if ($this->item->isSuper() && Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_USE_ROOT_PRICES)) {
                    $tierPrice->setAggregateChildren(true);
                }

                if ($tier['all_groups'] != 1) {
                    $tierPrice->setCustomerGroupUid($tier['cust_group']);
                }
                $price->addTierPriceGroup($tierPrice);
            }
        }
    }

    /**
     * @param $price Shopgate_Model_Catalog_Price
     */
    protected function _createGroupPriceNode(&$price)
    {
        foreach ($this->item->getData('group_price') as $group) {
            if (
                ($group['website_id'] == Mage::app()->getStore()->getWebsiteId() || $group['website_id'] == 0)
                && $price->getSalePrice() > $group['website_price']
            ) {
                $tierPrice = new Shopgate_Model_Catalog_TierPrice();

                $tierPrice->setFromQuantity(1);
                $tierPrice->setReduction($price->getSalePrice() - $group['website_price']);
                $tierPrice->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);
                $tierPrice->setCustomerGroupUid($group['cust_group']);
                if ($this->item->isSuper() && Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_USE_ROOT_PRICES)) {
                    $tierPrice->setAggregateChildren(true);
                }
                
                $price->addTierPriceGroup($tierPrice);
            }
        }
    }

    /**
     * set shipping
     */
    public function setShipping()
    {
        $shipping = new Shopgate_Model_Catalog_Shipping();
        $shipping->setAdditionalCostsPerUnit(false);
        $shipping->setCostsPerOrder(false);
        $shipping->setIsFree(false);

        parent::setShipping($shipping);
    }

    /**
     * set manufacturer
     */
    public function setManufacturer()
    {
        $title = $this->_getExportHelper()->getManufacturer($this->item);
        if (!empty($title)) {
            $manufacturer = new Shopgate_Model_Catalog_Manufacturer();
            $manufacturer->setUid($this->item->getManufacturer());
            $manufacturer->setTitle($title);
            $manufacturer->setItemNumber(false);
            parent::setManufacturer($manufacturer);
        }
    }

    /**
     * set visibility
     */
    public function setVisibility()
    {
        $level      = null;
        $visibility = new Shopgate_Model_Catalog_Visibility();
        switch ($this->item->getVisibility()) {
            case Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH:
                $level = Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG_AND_SEARCH;
                break;
            case Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG:
                $level = Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG;
                break;
            case Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH:
                $level = Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_SEARCH;
                break;
            case Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE:
                $level = Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_NOT_VISIBLE;
                break;
        }
        $visibility->setLevel($level);
        $visibility->setMarketplace(true);

        parent::setVisibility($visibility);
    }

    /**
     * set stock
     */
    public function setStock()
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = $this->item->getStockItem();
        $stock     = new Shopgate_Model_Catalog_Stock();
        $useStock  = false;
        if ($stockItem->getManageStock() && !$this->item->isSuper()) {
            switch ($stockItem->getBackorders() && $stockItem->getIsInStock()) {
                case Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY:
                case Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY:
                    break;
                default:
                    $useStock = true;
                    break;
            }
        }
        $stock->setUseStock((int)$useStock);
        if ($stock->getUseStock()) {
            $stock->setIsSaleable((int)$this->item->getIsSalable());
        } else {
            $stock->setIsSaleable(1);
        }

		$stock->setBackorders((int)$stockItem->getBackorders());
		$stock->setMaximumOrderQuantity((int)$stockItem->getMaxSaleQty());
		$stock->setMinimumOrderQuantity((int)$stockItem->getMinSaleQty());
		if (method_exists($stockItem, 'getStockQty')) {
			$stockQuantity = $stockItem->getStockQty();
		} else {
			$stockQuantity = $this->_getExportHelper()->getParentStockQuantity($this->item);
		}
		$stock->setStockQuantity((int)$stockQuantity);
        $stock->setAvailabilityText(
            $this->_getExportHelper()->getAvailableText(
                $this->item,
                $this->_getConfig()->getStoreViewId()
            )
        );

        parent::setStock($stock);
    }

    /**
     * set images
     */
    public function setImages()
    {
        $result = array();
        $images = $this->getProductImages();
        if (!empty($images)) {
            foreach ($images as $image) {
                $imagesItemObject = new Shopgate_Model_Media_Image();
                $imagesItemObject->setUrl($image['url']);
                $imagesItemObject->setTitle($image['title']);
                $imagesItemObject->setAlt($image['alt']);
                $imagesItemObject->setSortOrder($image['position']);
                $result[] = $imagesItemObject;
            }
        }
        parent::setImages($result);
    }
    
    /**
     * getting images as array
     * @return array
     */
    public function getProductImages()
    {
        $images = $this->_getProductImages();
        if ($this->_parent) {
            $parentImages = $this->_getProductImages(true);
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

        $images = $this->_compareImageObject($images);
        return $images;
    }


    /**
     * get product images
     *
     * @param bool $parent
     * @return array
     */
    protected function _getProductImages($parent = false)
    {
        $images = array();
        $product = $this->item;
        if ($parent) {
            $product = $this->_parent;
        }
        
        $mediaGallery = $this->_getExportHelper()->getMediaImages($product);
        if (!empty($mediaGallery)) {
            foreach ($mediaGallery as $image) {
                if ($image->getFile()) {
                    $imageConfig = Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_FIRST_PRODUCT_IMAGE);
                    if ($imageConfig == 'thumbnail' && $image->getFile() == $product->getThumbnail()) {
                        $position = -1;
                    } else if ($imageConfig == 'base' && $image->getFile() == $product->getImage()) {
                        $position = -1;
                    } else if ($imageConfig == 'small' && $image->getFile() == $product->getSmallImage()) {
                        $position = -1;
                    } else {
                        $position = $image->getPosition();
                    }
                    $_image = array(
                        'url'      => $image->getUrl(),
                        'title'    => $image->getLabel(),
                        'alt'      => $image->getLabel(),
                        'position' => $position
                    );

                    if (!array_key_exists($position, $images)) {
                        $images[$position] = $_image;
                    } else {
                        $images[] = $_image;
                    }

                }
            }
        }

        return $images;
    }

    /**
     * compare images
     * 
     * @param $images array
     * @return array
     */
    protected function _compareImageObject($images) 
    {
        $imageUrls = array();
        foreach ($images as $_imageObject) {
            if (!array_key_exists($imageUrls, $_imageObject['url'])) {
                $imageUrls[$_imageObject['url']] = $_imageObject;
            }
        }
        return array_values($imageUrls);
    }

    
    /**
     * set category path
     */
    public function setCategoryPaths()
    {
        $result = array();
        if ($this->_getExportHelper()->isProductVisibleInCategories($this->item)) {
            $itemsOrderOption = Mage::getStoreConfig(
                                    Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_ITEM_SORT

            );
            $linkedCategories = Mage::getResourceSingleton('shopgate/product')->getCategoryIdsAndPosition($this->item);

            foreach ($linkedCategories as $link) {
                $categoryItemObject = new Shopgate_Model_Catalog_CategoryPath();
                $categoryItemObject->setUid($link['category_id']);

                switch ($itemsOrderOption) { 
                    case Shopgate_Framework_Model_System_Config_Source_Item_Sort::SORT_TYPE_LAST_UPDATED:
                        $sortIndex     = Mage::getModel('core/date')->timestamp(strtotime($this->item->getUpdatedAt()));
                        $categoryItemObject->setSortOrder($sortIndex);
                        break;
                    case Shopgate_Framework_Model_System_Config_Source_Item_Sort::SORT_TYPE_NEWEST:
                        $sortIndex     = Mage::getModel('core/date')->timestamp(strtotime($this->item->getCreatedAt()));
                        $categoryItemObject->setSortOrder(Shopgate_Framework_Model_Export_Product_Csv::MAX_TIMESTAMP - $sortIndex);
                        break;
                    case Shopgate_Framework_Model_System_Config_Source_Item_Sort::SORT_TYPE_PRICE_DESC:
                        $sortIndex = round($this->item->getFinalPrice() * 100, 0);
                        $categoryItemObject->setSortOrder($sortIndex);
                        break;
                    case Shopgate_Framework_Model_System_Config_Source_Item_Sort::SORT_TYPE_POSITION:
                        $categoryItemObject->setSortOrder($link['max_position'] - $link['position']);
                        break;
                    default:
                        $categoryItemObject->setSortOrder($link['position']);
                }
                $result[$link['category_id']] = $categoryItemObject;
            }
        }

        parent::setCategoryPaths($result);
    }

    /**
     * set properties
     */
    public function setProperties()
    {
        if (empty($this->_ignoredProductAttributeCodes)) {
            $ignoredProductAttributeCodes        = array("manufacturer", "model");
            $ignoredProperties                   = explode(
                ",",
                Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_FILTER_PROPERTIES)
            );
            $ignoredProductAttributeCodes        = array_merge($ignoredProductAttributeCodes, $ignoredProperties);
            $this->_ignoredProductAttributeCodes = array_unique($ignoredProductAttributeCodes);
        }

        if (empty($this->_forcedProductAttributeCodes)) {
            $forcedProperties = explode(
                ",",
                Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_FORCE_PROPERTY_EXPORT)
            );
            $this->_forcedProductAttributeCodes = array_unique($forcedProperties);
        }

        $result = array();

	    $cacheKey = 'product_type_' . $this->item->getTypeId() . '_attributes_' . $this->item->getAttributeSetId();

	    $cache = Mage::app()->getCacheInstance();
	    $value = $cache->load($cacheKey);

	    if ($value !== false) {
		    $attributes = unserialize($value);
	    } else {
		    $attributes = $this->item->getAttributes();
		    $attrCache = array();
		    foreach ($attributes as $attribute) {
			    $code = $attribute->getAttributeCode();

			    /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
			    if ($attribute && $attribute->getIsVisibleOnFront() || in_array($code, $this->_forcedProductAttributeCodes)) {
				    if (in_array($code, $this->_ignoredProductAttributeCodes) && !in_array($code, $this->_forcedProductAttributeCodes)) {
					    continue;
				    }

				    $attrCache[$code] = array(
					    'id' => $attribute->getId(),
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
                  60
            );
	    }

	    foreach ($attributes as $code => $data) {
            $value = $this->item->getResource()->getAttribute($code)->getFrontend()->getValue($this->item);
            if (!empty($value) && !is_array($value)) {
                $propertyItemObject = new Shopgate_Model_Catalog_Property();
                $propertyItemObject->setUid($data['id']);
                $propertyItemObject->setLabel($data['label']);
                $propertyItemObject->setValue($value);
                $result[] = $propertyItemObject;
            }
        }

        parent::setProperties($result);
    }

    /**
     * set identifiers
     */
    public function setIdentifiers()
    {
        $result = array();

        $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
        $identifierItemObject->setType('SKU');
        $identifierItemObject->setValue($this->item->getSku());
        $result[] = $identifierItemObject;
        $this->_getIdentifierByType('ean', $result);
        $this->_getIdentifierByType('upc', $result);

        parent::setIdentifiers($result);
    }

    /**
     * @param $type
     * @param $result
     */
    protected function _getIdentifierByType($type, &$result)
    {
        $typeLabel = '';
        switch ($type) {
            case 'ean':
                $attributeCode = Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EAN_ATTR_CODE);
                $typeLabel     = 'EAN';
                break;
            case 'upc':
                $attributeCode = Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_UPC_ATTR_CODE);
                $typeLabel     = 'UPC';
                break;
        }

        if (!empty($attributeCode)) {
            $textValue      = $this->item->getAttributeText($attributeCode);
            $attributeValue = $this->item->getData($attributeCode);
            if (!empty($value) || !empty($attributeValue)) {
                $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
                $identifierItemObject->setType($typeLabel);
                $identifierItemObject->setValue(($textValue) ? $textValue : $attributeValue);
                $result[] = $identifierItemObject;
            }
        }
    }

    /**
     * set tags
     */
    public function setTags()
    {
        $result = array();
        $tags   = explode(',', $this->item->getMetaKeyword());

        foreach ($tags as $tag) {
            if (!ctype_space($tag) && !empty($tag)) {
                $tagItemObject = new Shopgate_Model_Catalog_Tag();
                $tagItemObject->setValue(trim($tag));
                $result[] = $tagItemObject;
            }
        }

        parent::setTags($result);
    }

    /**
     * set relations
     */
    public function setRelations()
    {
        $result = array();

        $crossSellIds = $this->item->getCrossSellProductIds();
        if (!empty($crossSellIds)) {
            $crossSellRelation = new Shopgate_Model_Catalog_Relation();
            $crossSellRelation->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL);
            $crossSellRelation->setValues($crossSellIds);
            $result[] = $crossSellRelation;
        }

        $upsellIds = $this->item->getUpSellProductIds();
        if (!empty($upsellIds)) {
            $upSellRelation = new Shopgate_Model_Catalog_Relation();
            $upSellRelation->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL);
            $upSellRelation->setValues($upsellIds);
            $result[] = $upSellRelation;
        }

        $relatedIds = $this->item->getRelatedProductIds();
        if (!empty($relatedIds)) {
            $relatedRelation = new Shopgate_Model_Catalog_Relation();
            $relatedRelation->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL);
            $relatedRelation->setValues($relatedIds);
            $result[] = $relatedRelation;
        }

        parent::setRelations($result);
    }

    /**
     * set attribute groups
     */
    public function setAttributeGroups()
    {
        if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $productTypeInstance = $this->item->getTypeInstance(true);
            if ($productTypeInstance == null || !method_exists($productTypeInstance, "getUsedProducts")) {
                return;
            }
            $configurableAttributes = $productTypeInstance->getConfigurableAttributes($this->item);
            $result                 = array();
            foreach ($configurableAttributes as $attribute) {
                /* @var $attribute Mage_Catalog_Model_Product_Type_Configurable_Attribute */
                $attributeItem = new Shopgate_Model_Catalog_AttributeGroup();
                $attributeItem->setUid($attribute->getAttributeId());
                $attributeItem->setLabel($attribute->getProductAttribute()->getStoreLabel());
                $result[] = $attributeItem;
            }
            parent::setAttributeGroups($result);
        }
    }

    /**
     * set inputs
     */
    public function setInputs()
    {
        $result = array();
        foreach ($this->item->getOptions() as $option) {
            /** @var Mage_Catalog_Model_Product_Option $option */
            $inputType = $this->_mapInputType($option->getType());

            $inputItem = new Shopgate_Model_Catalog_Input();
            $inputItem->setUid($option->getId());
            $inputItem->setType($inputType);
            $inputItem->setLabel($option->getTitle());
            $inputItem->setRequired($option->getIsRequire());
            $inputItem->setValidation($this->_buildInputValidation($inputType, $option));

            /**
             * add additional price for types without options
             */
            switch ($inputType) {
                case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT:
                case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_AREA:
                case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE:
                case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATE:
                case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATETIME:
                case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TIME:
                    $inputItem->setAdditionalPrice($this->_getInputValuePrice($option));
                    break;
                default :
                    $inputItem->setOptions($this->_buildInputOptions($option));
                    break;
            }

            $result[] = $inputItem;
        }

        if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $result = $this->_setBundleOptions($result);
        }

        parent::setInputs($result);
    }

    /**
     * @param $inputType
     * @param $option Mage_Catalog_Model_Product_Option
     *
     * @return Shopgate_Model_Catalog_Validation
     */
    protected function _buildInputValidation($inputType, $option)
    {
        $validation = new Shopgate_Model_Catalog_Validation();
        
        switch ($inputType) {
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_AREA:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_STRING;
                $validation->setValue($option->getMaxCharacters());
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_TYPE_FILE;
                $validation->setValue($option->getFileExtension());
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATE:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_DATE;
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATETIME:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_DATE;
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TIME:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_TIME;
                break;
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_RADIO:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX:
            case Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_MULTIPLE:
                $validationType = Shopgate_Model_Catalog_Validation::DEFAULT_VALIDATION_VARIABLE_STRING;
                break;
            default:
                return $validation;
        }

        $validation->setValidationType($validationType);

        return $validation;
    }

    /**
     * @param $option Mage_Catalog_Model_Product_Option
     *
     * @return array
     */
    protected function _buildInputOptions($option)
    {
        $optionValues = array();

        foreach ($option->getValues() as $id => $value) {
            /** @var Mage_Catalog_Model_Product_Option_Value $value */
            $inputOption = new Shopgate_Model_Catalog_Option();
            $inputOption->setUid($id);
            $inputOption->setLabel($value->getTitle());
            $inputOption->setAdditionalPrice($this->_getOptionValuePrice($value));
            $optionValues[] = $inputOption;
        }

        return $optionValues;
    }

    /**
     * @param $value Mage_Core_Model_Abstract
     * 
     * @return float
     */
    protected function _getOptionValuePrice($value)
    {
        if ($value->getPriceType() == 'percent') {
            $rulePrice = Mage::helper("shopgate/export")->calcProductPriceRule($this->item);
            $price     = $rulePrice * ($value->getPrice() / 100);
            return $price;
        }

        return $value->getPrice();
    }

    /**
     * @param Mage_Catalog_Model_Product_Option $value
     *
     * @return float
     */
    protected function _getInputValuePrice($value)
    {
        return $this->_getOptionValuePrice($value);
    }

    /**
     * @param $mageType
     *
     * @return string
     */
    protected function _mapInputType($mageType)
    {
        switch ($mageType) {
            case "field":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT;
                break;
            case "area":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_AREA;
                break;
            case "file":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE;
                break;
            case "select":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case "drop_down":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case "radio":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case "checkbox":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case "multiple":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case "multi":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT;
                break;
            case "date":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATE;
                break;
            case "date_time":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_DATETIME;
                break;
            case "time":
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TIME;
                break;
            default:
                $inputType = Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT;
        }

        return $inputType;
    }

    /**
     *
     */
    public function setChildren()
    {
        $children = array();
        if ($this->item->isConfigurable()) {
            $childProductIds = $this->item->getTypeInstance()->getUsedProductIds();
            foreach ($childProductIds as $child) {
                $configChild = Mage::getModel('catalog/product')
                                    ->setStoreId($this->_getConfig()->getStoreViewId())
                                    ->load($child);
                    $childProducts[] = $configChild;
            }
        }

        if ($this->item->isGrouped()) {
            $childIds      = $this->item->getTypeInstance()->getChildrenIds($this->item->getId());
            $childProducts = array();
            foreach ($childIds as $childOption) {
                foreach ($childOption as $childId) {
                    /** @var Mage_Catalog_Model_Product $groupedChild */
                    $groupedChild = Mage::getModel('catalog/product')
                                        ->setStoreId($this->_getConfig()->getStoreViewId())
                                        ->load($childId);
                    if ($groupedChild->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                        $childProducts[] = $groupedChild;    
                    }
                }
            }
        }

        $oldVersion = $this->_getConfigHelper()->getIsMagentoVersionLower15();

        if (!empty($childProducts)) {
            foreach ($childProducts as $childProduct) {
                /** @var Mage_Catalog_Model_Product $childProduct */
                /** @var Shopgate_Framework_Model_Export_Product_Xml $child */
                $child = Mage::getModel('shopgate/export_product_xml');
                $child->setItem($childProduct);
                $child->setParentItem($this->item);
                $child->setData('uid', $this->item->getId() . '-' . $childProduct->getId());
                $child->setIsChild(true);
                $child->setAttributes($this->item);
                $child->setFireMethodsForChildren();
                $child->generateData();

                $children[] = $child;
                if (!$oldVersion) {
                    $childProduct->clearInstance();    
                }
            }
        }

        parent::setChildren($children);
    }

    /**
     * @param Mage_Catalog_Model_Product $parent
     */
    public function setAttributes($parent)
    {
        $result = array();
        if ($this->getIsChild() && $parent->isConfigurable()) {
            /** @var Mage_Catalog_Model_Product_Type_Configurable $productTypeInstance */
            $productTypeInstance = $parent->getTypeInstance(true);
            $allowAttributes     = $productTypeInstance->getConfigurableAttributes($parent);
            foreach ($allowAttributes as $attribute) {
                /** @var Mage_Catalog_Model_Product_Type_Configurable_Attribute $attribute */

                $itemAttribute = new Shopgate_Model_Catalog_Attribute();
                $attribute     = $attribute->getProductAttribute();

                if ($attribute == null) {
                    continue;
                }

                $itemAttribute->setGroupUid($attribute->getAttributeId());
                $attrValue = $this->item->getResource()->getAttribute($attribute->getAttributeCode())->getFrontend();
                $itemAttribute->setLabel($attrValue->getValue($this->item));
                $result[] = $itemAttribute;
            }
        }
        parent::setAttributes($result);
    }

    /**
     *
     */
    public function setFireMethodsForChildren()
    {
        $this->fireMethods = array(
            'setLastUpdate',
            'setName',
            'setTaxPercent',
            'setTaxClass',
            'setCurrency',
            'setDescription',
            'setDeeplink',
            'setPromotionSortOrder',
            'setInternalOrderInfo',
            'setAgeRating',
            'setWeight',
            'setWeightUnit',
            'setPrice',
            'setShipping',
            'setManufacturer',
            'setVisibility',
            'setStock',
            'setImages',
            'setCategoryPaths',
            'setProperties',
            'setIdentifiers',
            'setTags',
            'setInputs',
            'setChildren',
        );
    }

    /**
     * @return string|void
     */
    public function setDisplayType()
    {
        if ($this->item->isGrouped()) {
            parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_LIST);
        }

        if ($this->item->isConfigurable()) {
            parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_SELECT);
        }

        if ($this->item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_SIMPLE);
        }
    }


    /**
     * @param Mage_Catalog_Model_Product $parent
     */
    public function setParentItem(Mage_Catalog_Model_Product $parent)
    {
        $this->_parent = $parent;
    }

    /**
     * @param $inputs
     *
     * @return array
     */
    protected function _setBundleOptions($inputs = array())
    {
        $bundleOptions       = $this->item->getPriceModel()->getOptions($this->item);
        $isGross             = Mage::getStoreConfig(self::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
                                                    $this->_getConfig()->getStoreViewId());
        $stock               = parent::getStock();
        $selectionQuantities = array();

        foreach ($bundleOptions as $bundleOption) {

	        $optionValues = array();

            /* @var $bundleOption Mage_Bundle_Model_Option */
            if (!is_array($bundleOption->getSelections())) {
                $stock->setIsSaleable(false);
                $bundleOption->setSelections(array());
            }

	        $optionPrices       = array();
	        $cheapestOptionId   = null;
	        $cheapestPrice      = null;

	        // fetch id of cheapest option
	        foreach ($bundleOption->getSelections() as $selection) {
		        $selectionPrice = $this->item
			        ->getPriceModel()
			        ->getSelectionFinalPrice($this->item, $selection, 1, $selection->getSelectionQty());

		        $selectionPrice = Mage::helper('tax')->getPrice($selection, $selectionPrice, $isGross);

		        if ($cheapestPrice === null
		            || $cheapestPrice > $selectionPrice
		        ) {
			        $cheapestPrice    = $selectionPrice;
			        $cheapestOptionId = $selection->getSelectionId();
		        }

		        $optionPrices[$selection->getSelectionId()] = $selectionPrice;
	        }

            foreach ($bundleOption->getSelections() as $selection) {
                $option = new Shopgate_Model_Catalog_Option();
                /** @var $selection Mage_Catalog_Model_Product */

	            $selectionId = $selection->getSelectionId();
                $qty            = max(1, (int)$selection->getSelectionQty());
                $selectionPrice = $optionPrices[$selectionId];

                $selectionName = $qty > 1 ? $qty . " x " : '';
                $selectionName .= $this->_getMageCoreHelper()->htmlEscape($selection->getName());


                if (!array_key_exists($selectionId, $selectionQuantities)) {
                    $selectionQuantities[$selectionId] = 0;
                }

                if ($this->item->getStockItem()->getManageStock() && $selection->isSaleable()
                    && $this->item->getStockItem()->getQty() > 0
                ) {
                    if ($selectionQuantities[$selectionId] !== null) {
                        $selectionQuantities[$selectionId] += $this->item->getStockItem()->getQty();
                    }
                } else {
                    if (!$this->item->getStockItem()->getManageStock()) {
                        $selectionQuantities[$selectionId] = null;
                    } else {
                        if (!$selection->isSaleable() && $this->item->getStockItem()->getBackorders()) {
                            $selectionQuantities[$selectionId] = null;
                        } else {
                            $selectionQuantities[$selectionId] = 0;
                        }
                    }
                }

                $option->setUid($selection->getSelectionId());
                $option->setLabel($selectionName);

	            // reset selection price, in this case the bundle parent is already configured
	            // with the price of the cheapest bundle configuration
	            if ($this->item->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC) {
                    if ($cheapestOptionId == $selection->getSelectionId() && $selection->getOption()->getRequired()) {
                        $selectionPrice = 0;
                    } elseif ($selection->getOption()->getRequired()) {
                        $selectionPrice = $selectionPrice-$optionPrices[$cheapestOptionId];
                    }
	            }

                $option->setAdditionalPrice($selectionPrice);
                $optionValues[] = $option;
                if ($selectionQuantities[$selectionId] === null) {
                    unset($selectionQuantities[$selectionId]);
                }
            }
            $inputItem = new Shopgate_Model_Catalog_Input();
            $inputItem->setUid($bundleOption->getId());
            $inputItem->setType($this->_mapInputType($bundleOption->getType()));
            $title = ($bundleOption->getTitle()) ? $bundleOption->getTitle() : $bundleOption->getDefaultTitle();
            $inputItem->setLabel($title);
            $inputItem->setValidation($this->_buildInputValidation($inputItem->getType(), null));
            $inputItem->setRequired($bundleOption->getRequired());
            $inputItem->setOptions($optionValues);
            $inputs[] = $inputItem;
        }

        $stockQty = count($selectionQuantities) ? min($selectionQuantities) : 0;
        $stock->setStockQuantity($stockQty);
        if (!count($selectionQuantities)) {
            $stock->setUseStock(false);
        }
        $this->setStock($stock);

        return $inputs;
    }

    /**
     * @return Mage_Core_Helper_Data
     */
    protected function _getMageCoreHelper()
    {
        return Mage::helper('core');
    }

    /**
     * @param $price
     * @return float
     */
    protected function _formatPrice($price)
    {
        if (Mage::app()->getStore()->getCurrentCurrency() && Mage::app()->getStore()->getBaseCurrency()) {
            $value = Mage::app()->getStore()->getBaseCurrency()->convert($price, Mage::app()->getStore()->getCurrentCurrency());
        } else {
            $value = $price;
        }
        return $value;
    }
} 
