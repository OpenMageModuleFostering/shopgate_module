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
 * @deprecated  v.2.9.18 - use Shopgate_Framework_Model_Payment_Pp_Paypal instead
 * @package     Shopgate_Framework_Model_Payment_Express
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de
 * @author      Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Express
{

    /**
     * create new order for paypal express (type wspp)
     *
     * @deprecated v.2.9.18
     * @param $quote            Mage_Sales_Model_Quote
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public function createNewOrder($quote)
    {
        return Mage::getModel('shopgate/payment_pp_paypal', new ShopgateOrder())->createNewOrder($quote);
    }

    /**
     * @deprecated v.2.9.18
     * @param $order            Mage_Sales_Model_Order
     * @param $shopgateOrder    ShopgateOrder
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order, $shopgateOrder)
    {
        return Mage::getModel('shopgate/payment_pp_paypal', $shopgateOrder)->manipulateOrderWithPaymentData($order);
    }

    /**
     * @deprecated v.2.9.18
     * @param $quote            Mage_Sales_Model_Quote
     * @param $data             array
     * @return Mage_Sales_Model_Quote
     */
    public function prepareQuote($quote, $data)
    {
        return Mage::getModel('shopgate/payment_pp_paypal', new ShopgateOrder())->prepareQuote($quote, $data);
    }

    /**
     * @return Shopgate_Framework_Helper_Payment_Wspp
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('shopgate/payment_wspp');
    }
}