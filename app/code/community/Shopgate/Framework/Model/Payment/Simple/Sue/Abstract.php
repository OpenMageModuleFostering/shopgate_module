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
 * Fallback handler for all SUE payment method needs.
 *
 * Class Shopgate_Framework_Model_Payment_Simple_Sue_Abstract
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Simple_Sue_Abstract extends Shopgate_Framework_Model_Payment_Abstract
{
    const PAYMENT_IDENTIFIER = ShopgateOrder::SUE;
    const MODULE_CONFIG      = 'Paymentnetwork_Pnsofortueberweisung';

    /**
     * Add invoice to a paid order
     * todo: v118 may need a transaction ID for refunding
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public function manipulateOrderWithPaymentData($magentoOrder)
    {
        $shopgateOrder = $this->getShopgateOrder();

        if ($shopgateOrder->getIsPaid()) {
            $invoice = $this->_getPaymentHelper()->createOrderInvoice($magentoOrder);
            $invoice->setIsPaid(true);
            $invoice->pay();
            $invoice->save();
            $magentoOrder->addRelatedObject($invoice);
        }

        $magentoOrder = parent::manipulateOrderWithPaymentData($magentoOrder);

        return $magentoOrder;
    }

    /**
     * Sets order status backup implementation
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return Mage_Sales_Model_Order
     */
    public function setOrderStatus($magentoOrder)
    {
        $magentoOrder = parent::setOrderStatus($magentoOrder);

        /**
         * Old versions where status is not set by default
         */
        if (!$magentoOrder->getShopgateStatusSet()) {
            $state   = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
            $status  = $this->_getHelper()->getStatusFromState($state);
            $message = $this->_getHelper()->__('[SHOPGATE] Using default status as no native plugin status is set');
            $magentoOrder->setState($state, $status, $message);
            $magentoOrder->setShopgateStatusSet(true);
        }

        return $magentoOrder;
    }

}