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
 * User: Steffen Meuser
 * Date: 16.05.14
 * Time: 18:07
 * E-Mail: steffen.meuser@shopgate.com
 */

/**
 * config helper
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Helper_Coupon extends Mage_Core_Helper_Abstract
{
    const COUPON_ATTRIUBTE_SET_NAME = 'Shopgate Coupon';
    const COUPON_PRODUCT_SKU        = 'shopgate-coupon';

    /**
     * Determines if a product is a Shopgate Coupon
     *
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    public function isShopgateCoupon(Mage_Catalog_Model_Product $product)
    {
	    $attributeSetModel = Mage::getModel("eav/entity_attribute_set")
		    ->load($product->getAttributeSetId());

	    if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL
		    && $attributeSetModel->getAttributeSetName() == self::COUPON_ATTRIUBTE_SET_NAME
	    ) {
            return true;
        }

        return false;
    }

    /**
     * Sets missing product Attributes for virutal product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Product
     */
    public function prepareShopgateCouponProduct(Mage_Catalog_Model_Product $product)
    {
        $product->setData('weight', 0);
        $product->setData('tax_class_id', $this->_getTaxClassId());
        $product->setData('attribute_set_id', $this->_getAttributeSetId());
        $product->setData('stock_data', $this->_getStockData());
        $product->setData('visibility', 1);
        $product->setData('status', 1);

        return $product;
    }

    /**
     * Offers a suitable tax_class_id for Shopgate-Coupons
     *
     * @return int
     */
    protected function _getTaxClassId()
    {
        return 0;
    }

    /**
     * Offers an attribute set for Shopgate-Coupons
     *
     * @return int
     */
    protected function _getAttributeSetId()
    {
        if ($attributeSet = $this->_getShopgateCouponAttributeSet()) {
            return $attributeSet->getId();
        } else {
            return $this->_createShopgateCouponAttributeSet()->getId();
        }
    }

    /**
     * @return null|Mage_Eav_Model_Entity_Attribute_Set
     */
    protected function _getShopgateCouponAttributeSet()
    {
        $collection = Mage::getModel('eav/entity_attribute_set')
                          ->getCollection()
                          ->addFieldToFilter('attribute_set_name', self::COUPON_ATTRIUBTE_SET_NAME);

        if (count($collection->getItems())) {
            return $collection->getFirstItem();
        }

        return null;
    }

    /**
     * @return Mage_Eav_Model_Entity_Attribute_Set|null
     */
    protected function _createShopgateCouponAttributeSet()
    {
        $entityTypeId = Mage::getModel('catalog/product')
                            ->getResource()->getEntityType()->getId();

        $attributeSet = Mage::getModel('eav/entity_attribute_set')
                            ->setEntityTypeId($entityTypeId)
                            ->setAttributeSetName(self::COUPON_ATTRIUBTE_SET_NAME);

        $attributeSet->validate();
        $attributeSet->save();

        return $this->_getShopgateCouponAttributeSet();
    }

    /**
     * Delivers an stock_item Dummy Object
     *
     * @return multitype:number
     */
    protected function _getStockData()
    {
        $stockData = array(
            "qty"                         => 1,
            "use_config_manage_stock"     => 0,
            "is_in_stock"                 => 1,
            "use_config_min_sale_qty"     => 1,
            "use_config_max_sale_qty"     => 1,
            "use_config_notify_stock_qty" => 1,
            "use_config_backorders"       => 1,
        );

        return $stockData;
    }
}