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
class Shopgate_Framework_Model_Carrier_Fix
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * @var string
     */
    protected $_code = 'shopgate';
    /**
     * @var string
     */
    protected $_method = 'fix';
    /**
     * @var bool
     */
    protected $_isFixed = false;
    /**
     * @var int
     */
    protected $_numBoxes = 1;

    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return bool | Mage_Shipping_Model_Rate_Result | null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        /* @var $sgOrder ShopgateOrder */
        $sgOrder = Mage::getSingleton('core/session')->getData('shopgate_order');
        if (!$sgOrder) {
            return false;
        }

        $shippingInfo   = $sgOrder->getShippingInfos();
        $carrierTitle   = Mage::getStoreConfig('shopgate/orders/shipping_title');
        $methodTitle    = $shippingInfo->getName();
        $displayName    = $shippingInfo->getDisplayName();
        if (!empty($displayName)) {
            $splitTitle = explode('-', $displayName);
            if ($splitTitle && is_array($splitTitle) && count($splitTitle) >= 2) {
                $carrierTitle = $splitTitle[0];
                $carrierTitle = trim($carrierTitle);
                $methodTitle  = $splitTitle[1];
                $methodTitle  = trim($methodTitle);
            }
        }

        $method = Mage::getModel('shipping/rate_result_method');
        $method->setData('carrier', $this->_code);
        $method->setData('carrier_title', $carrierTitle);
        $method->setData('method', $this->_method);
        $method->setData('method_title', $methodTitle);

        $scopeId             = Mage::helper('shopgate/config')->getConfig()->getStoreViewId();
        $shippingIncludesTax = Mage::helper('tax')->shippingPriceIncludesTax($scopeId);
        $shippingTaxClass    = Mage::helper('tax')->getShippingTaxClass($scopeId);

        $amountNet   = $shippingInfo->getAmountNet();
        $amountGross = $shippingInfo->getAmountGross();

        if ($shippingIncludesTax) {
            if (Mage::helper('shopgate/config')->getIsMagentoVersionLower19()
                || !Mage::helper('tax')->isCrossBorderTradeEnabled($scopeId)
            ) {
                $calc        = Mage::getSingleton('tax/calculation');
                $store       = Mage::app()->getStore($scopeId);
                $taxRequest  = $calc->getRateOriginRequest($store)
                                    ->setData('product_class_id', $shippingTaxClass);
                $rate        = $calc->getRate($taxRequest) / 100;
                $amountGross = $amountNet * (1 + $rate);
            }
            $shippingAmount = $amountGross;
        } else {
            $shippingAmount = $amountNet;
        }

        $exchangeRate = Mage::app()->getStore()->getCurrentCurrencyRate();
        $method->setPrice($shippingAmount / $exchangeRate);

        $result = Mage::getModel('shipping/rate_result');
        $result->append($method);

        return $result;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return Mage::helper('shopgate')->isShopgateApiRequest();
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return array(
            $this->_method => $this->getConfigData('name')
        );
    }
}
