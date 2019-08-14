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
 * Coupon & cart rule helper
 *
 * @author  Shopgate GmbH, 35510 Butzbach, DE
 * @package Shopgate_Framework
 */
class Shopgate_Framework_Helper_Coupon extends Mage_Core_Helper_Abstract
{
    const COUPON_ATTRIUBTE_SET_NAME = 'Shopgate Coupon';
    const COUPON_PRODUCT_SKU        = 'shopgate-coupon';

    /**
     * const to detect coupons, which just represent cart rules
     */
    const CART_RULE_COUPON_CODE = '1';

    protected $_attributeSet = null;

    /**
     * Determines if a product is a Shopgate Coupon
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return boolean
     */
    public function isShopgateCoupon(Mage_Catalog_Model_Product $product)
    {
        $attributeSetModel = Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId());

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
     *
     * @return Mage_Catalog_Model_Product
     */
    public function prepareShopgateCouponProduct(Mage_Catalog_Model_Product $product)
    {
        $product->setData('weight', 0);
        $product->setData('tax_class_id', $this->_getTaxClassId());
        $product->setData('attribute_set_id', $this->_getAttributeSetId());
        $product->setData('stock_data', $this->_getStockData());
        $product->setData('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
        $product->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $product->setData('type_id', Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL);

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
        return $this->_getShopgateCouponAttributeSet()->getId();
    }

    /**
     * @return null|Mage_Eav_Model_Entity_Attribute_Set
     */
    protected function _getShopgateCouponAttributeSet()
    {
        if (!$this->_attributeSet) {
            $collection = Mage::getModel('eav/entity_attribute_set')
                              ->getCollection()
                              ->addFieldToFilter('attribute_set_name', self::COUPON_ATTRIUBTE_SET_NAME);

            if (count($collection->getItems())) {
                $this->_attributeSet = $collection->getFirstItem();
            } else {
                $this->_attributeSet = $this->_createShopgateCouponAttributeSet();
            }
        }

        return $this->_attributeSet;
    }

    /**
     * @return Mage_Eav_Model_Entity_Attribute_Set|null
     */
    protected function _createShopgateCouponAttributeSet()
    {
        $entityTypeId = Mage::getModel('catalog/product')
                            ->getResource()->getEntityType()->getId();

        /** @var Mage_Eav_Model_Entity_Attribute_Set $attributeSet */
        $attributeSet = Mage::getModel('eav/entity_attribute_set')
                            ->setEntityTypeId($entityTypeId)
                            ->setAttributeSetName(self::COUPON_ATTRIUBTE_SET_NAME);

        $attributeSet->validate();
        $this->_attributeSet = $attributeSet->save();

        return $this->_getShopgateCouponAttributeSet();
    }

    /**
     * Delivers an stock_item Dummy Object
     *
     * @return array
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

    /**
     * Create magento coupon product from object
     *
     * @param Varien_Object $coupon
     *
     * @return Mage_Catalog_Model_Product
     */
    public function createProductFromShopgateCoupon(Varien_Object $coupon)
    {
        /* @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product');
        $id      = $product->getIdBySku($coupon->getItemNumber());
        $product->load($id);

        $product = $this->prepareShopgateCouponProduct($product);
        $product->setPriceCalculation(false);
        $product->setName($coupon->getName());
        $product->setSku($coupon->getItemNumber());
        $product->setPrice($coupon->getUnitAmountWithTax());
        $product->setStoreId(Mage::app()->getStore()->getStoreId());

        if (!$product->getId()) {
            $oldStoreId = Mage::app()->getStore()->getStoreId();
            Mage::app()->setCurrentStore(0);
            $product->save();
            Mage::app()->setCurrentStore($oldStoreId);
        }

        return $product;
    }

    /**
     * Check coupons for validation and apply shopping cart price rules to the cart
     *
     * @param Mage_Checkout_Model_Cart $mageCart
     * @param ShopgateCart             $cart
     * @param bool                     $useTaxClasses
     *
     * @return ShopgateExternalCoupon[]
     * @throws ShopgateLibraryException
     */
    public function checkCouponsAndCartRules($mageCart, ShopgateCart $cart, $useTaxClasses)
    {
        /* @var $mageQuote Mage_Sales_Model_Quote */
        /* @var $mageCart Mage_Checkout_Model_Cart */
        /* @var $mageCoupon Mage_SalesRule_Model_Coupon */
        /* @var $mageRule Mage_SalesRule_Model_Rule */
        $mageQuote = $mageCart->getQuote();
        $mageQuote->setTotalsCollectedFlag(false)->collectTotals();

        $externalCoupons    = array();
        $validCouponsInCart = 0;
        $returnEmptyCoupon  = false;
        $appliedRules       = $mageQuote->getAppliedRuleIds();
        $totals             = $mageQuote->getTotals();
        $discountAmount     = empty($totals['discount']) ? 0 : $totals['discount']->getValue();

        if (!$cart->getExternalCoupons() && empty($discountAmount)) {
            return array();
        }

        foreach ($cart->getExternalCoupons() as $coupon) {
            if ($coupon->getCode() === self::CART_RULE_COUPON_CODE) {
                $returnEmptyCoupon = true;
                continue;
            }

            $externalCoupon = $this->validateExternalCoupon($coupon, $mageQuote, $useTaxClasses);

            if ($externalCoupon->getIsValid()) {
                $validCouponsInCart++;
            }
            if ($validCouponsInCart > 1) {
                $errorCode = ShopgateLibraryException::COUPON_TOO_MANY_COUPONS;
                $externalCoupon->setIsValid(false);
                $externalCoupon->setNotValidMessage(ShopgateLibraryException::getMessageFor($errorCode));
            }
            $externalCoupons[] = $externalCoupon;
        }

        if (!empty($discountAmount) && $validCouponsInCart == 0) {
            try {
                $totals = $mageQuote->getTotals();
                if (isset($totals['discount'])) {
                    $discount = $totals['discount'];
                    $coupon   = new ShopgateExternalCoupon();
                    $coupon->setIsValid(true);
                    $coupon->setCode(self::CART_RULE_COUPON_CODE);
                    $title = $discount->getTitle();
                    $title = empty($title) ? Mage::helper('sales')->__('Discount') : $title;
                    $coupon->setName($title);
                    $coupon->setDescription($discount->getTitle());
                    $amountCoupon = abs($discount->getValue());
                    if ($useTaxClasses) {
                        $coupon->setAmountGross($amountCoupon);
                    } else {
                        $coupon->setAmountNet($amountCoupon);
                    }
                    $coupon->setCurrency(Mage::app()->getStore()->getCurrentCurrencyCode());
                    $coupon->setInternalInfo(
                        Mage::helper('shopgate')->getConfig()->jsonEncode(array('rule_ids' => $appliedRules))
                    );
                    $externalCoupons[] = $coupon;
                    $returnEmptyCoupon = false;
                }
            } catch (Exception $e) {
                ShopgateLogger::getInstance()->log(
                    "Could not add rule with id " . $appliedRules . " to quote",
                    ShopgateLogger::LOGTYPE_DEBUG
                );

                return array();
            }
        }

        if ($returnEmptyCoupon) {
            $coupon = new ShopgateExternalCoupon();
            $coupon->setCode(self::CART_RULE_COUPON_CODE);
            $coupon->setName(Mage::helper('sales')->__('Discount'));
            $coupon->setIsValid(false);
            $externalCoupons[] = $coupon;
        }

        return $externalCoupons;
    }

    /**
     * Checks a coupon for validation
     *
     * @param ShopgateExternalCoupon $coupon
     * @param Mage_Sales_Model_Quote $mageQuote
     * @param bool                   $useTaxClasses
     *
     * @return ShopgateExternalCoupon
     * @throws ShopgateLibraryException
     */
    public function validateExternalCoupon($coupon, $mageQuote, $useTaxClasses)
    {
        /** @var ShopgateExternalCoupon $coupon */
        $externalCoupon = new ShopgateExternalCoupon();
        $externalCoupon->setIsValid(true);
        $externalCoupon->setCode($coupon->getCode());

        try {
            $mageQuote->setCouponCode($coupon->getCode());
            $mageQuote->setTotalsCollectedFlag(false)->collectTotals();
            $totals = $mageQuote->getTotals();
        } catch (Exception $e) {
            $externalCoupon->setIsValid(false);
            $externalCoupon->setNotValidMessage($e->getMessage());
        }

        if (Mage::helper('shopgate/config')->getIsMagentoVersionLower1410()) {
            $mageRule   = Mage::getModel('salesrule/rule')->load($coupon->getCode(), 'coupon_code');
            $mageCoupon = $mageRule;
        } else {
            $mageCoupon = Mage::getModel('salesrule/coupon')->load($coupon->getCode(), 'code');
            $mageRule   = Mage::getModel('salesrule/rule')->load($mageCoupon->getRuleId());
        }

        if ($mageRule->getId() && $mageQuote->getCouponCode()) {
            $discountName = isset($totals['discount'])
                ? $totals['discount']->getTitle()
                : $mageRule->getDescription();

            $couponInfo              = array();
            $couponInfo["coupon_id"] = $mageCoupon->getId();
            $couponInfo["rule_id"]   = $mageRule->getId();

            $amountCoupon = $mageQuote->getSubtotal() - $mageQuote->getSubtotalWithDiscount();
            $storeLabel   = $mageRule->getStoreLabel(Mage::app()->getStore()->getId());
            $externalCoupon->setName($storeLabel ? $storeLabel : $mageRule->getName());
            $externalCoupon->setDescription($discountName);
            $externalCoupon->setIsFreeShipping((bool)$mageQuote->getShippingAddress()->getFreeShipping());
            $externalCoupon->setInternalInfo(Mage::helper('shopgate')->getConfig()->jsonEncode($couponInfo));
            if ($useTaxClasses) {
                $externalCoupon->setAmountGross($amountCoupon);
            } else {
                $externalCoupon->setAmountNet($amountCoupon);
            }
            if (!$amountCoupon && !$externalCoupon->getIsFreeShipping()) {
                $externalCoupon->setIsValid(0);
                $externalCoupon->setAmount(0);
                $externalCoupon->setNotValidMessage(
                    Mage::helper('shopgate')->__(
                        'Coupon code "%s" is not valid.',
                        Mage::helper('core')->escapeHtml($coupon->getCode())
                    )
                );
            }
        } else {
            $externalCoupon->setIsValid(0);
            $externalCoupon->setAmount(0);
            $externalCoupon->setNotValidMessage(
                Mage::helper('shopgate')->__(
                    'Coupon code "%s" is not valid.',
                    Mage::helper('core')->escapeHtml($coupon->getCode())
                )
            );
        }
        $externalCoupon->setCurrency(Mage::app()->getStore()->getCurrentCurrencyCode());

        return $externalCoupon;
    }

    /**
     * Add coupon from this system to quote
     *
     * @param ShopgateCartBase $order
     *
     * @return ShopgateCartBase $order
     */
    public function removeCartRuleCoupons(ShopgateCartBase $order)
    {
        $externalCoupons = array();
        foreach ($order->getExternalCoupons() as $coupon) {
            /* @var $coupon ShopgateExternalCoupon */
            if ($coupon->getCode() !== self::CART_RULE_COUPON_CODE) {
                $externalCoupons[] = $coupon;
            }
        }
        $order->setExternalCoupons($externalCoupons);

        return $order;
    }
}