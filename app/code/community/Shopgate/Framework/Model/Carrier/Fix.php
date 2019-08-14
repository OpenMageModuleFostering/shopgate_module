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
 *  @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * User: Peter Liebig
 * Date: 22.01.14
 * Time: 15:06
 * E-Mail: p.liebig@me.com
 */

/**
 * carrier fix model
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
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
     * @return bool|Mage_Shipping_Model_Rate_Result|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $result = Mage::getModel('shipping/rate_result');

        $amount = array(
            "shipping" => 0,
            "payment"  => 0
        );

        /* @var $sgOrder ShopgateOrder */
        $sgOrder = Mage::getSingleton("core/session")->getData("shopgate_order");
        if (!$sgOrder) {
            return false;
        }

        $shippingInfo = $sgOrder->getShippingInfos();
        $carrierTitle = Mage::getStoreConfig("shopgate/orders/shipping_title");
        $methodTitle  = $shippingInfo->getName();
        $displayName  = $shippingInfo->getDisplayName();
        if (!empty($displayName)) {
            $splittedTitle = explode('-', $displayName);
            if ($splittedTitle && is_array($splittedTitle) && count($splittedTitle) >= 2) {
                $carrierTitle = $splittedTitle[0];
                $carrierTitle = trim($carrierTitle);
                $methodTitle  = $splittedTitle[1];
                $methodTitle  = trim($methodTitle);
            }
        }

        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($carrierTitle);
        $method->setMethod($this->_method);
        $method->setMethodTitle($methodTitle);

        $storeViewId = Mage::helper("shopgate/config")->getConfig()->getStoreViewId();
        $shippingIncludesTax = Mage::helper("tax")->shippingPriceIncludesTax($storeViewId);

        $amount['shipping'] = $shippingIncludesTax ? $shippingInfo->getAmountGross() : $shippingInfo->getAmountNet();

        $amountShopPayment = $sgOrder->getAmountShopPayment();
        if ($amountShopPayment >= 0) {
            // set payment fee only if payment fee is positive or 0
            // and its not cod with phoenix_cod active
            if ($sgOrder->getPaymentMethod() != ShopgateOrder::COD
                || (!Mage::getConfig()->getModuleConfig('Phoenix_CashOnDelivery')->is('active', 'true')
                    && !Mage::getConfig()->getModuleConfig('MSP_CashOnDelivery')->is('active', 'true'))
            ) {
                $amount["payment"] = $this->_getNetForGrossShipping($amountShopPayment);
            }
        }

        $exchangeRate = Mage::app()->getStore()->getCurrentCurrencyRate();
        $method->setPrice(array_sum($amount) / $exchangeRate);
        $result->append($method);

        return $result;
    }

    /**
     * @param  float $amount
     * @param  bool  $amountContainsTax
     * @return float
     */
    protected function _getNetForGrossShipping($amount, $amountContainsTax = true)
    {
        $storeViewId = Mage::helper("shopgate/config")->getConfig()->getStoreViewId();
        $taxClassId  = Mage::helper("tax")->getShippingTaxClass($storeViewId);

        $pseudoProduct = new Varien_Object();
        $pseudoProduct->setTaxClassId($taxClassId);

        $returnIncludesTax = Mage::helper("tax")->shippingPriceIncludesTax($storeViewId);
        $customerTaxClass  = null;

        $amount = Mage::helper("tax")->getPrice(
                      $pseudoProduct,
					  $amount,
                      $returnIncludesTax,
                      null,
                      null,
                      null,
                      $storeViewId,
                      $amountContainsTax
        );
        return $amount;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return Mage::helper("shopgate")->isShopgateApiRequest();
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
