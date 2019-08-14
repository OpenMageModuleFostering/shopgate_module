<?php
/**
 * User: pliebig
 * Date: 20.08.14
 * Time: 22:20
 * E-Mail: p.liebig@me.com, peter.liebig@magcorp.de
 */

/**
 * Deprecating as all simple payment methods will be handled inside Simple folder
 *
 * @deprecated  v.2.9.18 - use Shopgate_Framework_Model_Payment_Simple_Mws instead
 * @package     Shopgate_Framework_Model_Payment_Amazon
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 * @author      Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Amazon
{
    /**
     * Passing a fake ShopgateOrder to avoid error thrown
     *
     * @deprecated v.2.9.18
     * @param $quote            Mage_Sales_Model_Quote
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public function createNewOrder($quote)
    {
        return Mage::getModel('shopgate/payment_simple_mws', new ShopgateOrder())->createNewOrder($quote);
    }

    /**
     *
     * @deprecated v.2.9.18
     * @param $order            Mage_Sales_Model_Order
     * @param $shopgateOrder    ShopgateOrder
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order, $shopgateOrder)
    {
        return Mage::getModel('shopgate/payment_simple_mws', $shopgateOrder)->manipulateOrderWithPaymentData($order);
    }

    /**
     * Passing a fake ShopgateOrder to avoid error thrown
     *
     * @deprecated v.2.9.18
     * @param $quote    Mage_Sales_Model_Quote
     * @param $payment  Mage_Payment_Model_Method_Abstract|Creativestyle_AmazonPayments_Model_Payment_Advanced
     * @param $info     array
     * @return Mage_Sales_Model_Quote
     */
    public function prepareQuote($quote, $payment, $info)
    {
        return Mage::getModel('shopgate/payment_simple_mws', new ShopgateOrder())->prepareQuote($quote, $info);
    }
}

