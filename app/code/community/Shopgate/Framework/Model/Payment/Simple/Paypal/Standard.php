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
 * PayPal Standard handler
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Simple_Paypal_Standard
    extends Shopgate_Framework_Model_Payment_Pp_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const PAYMENT_IDENTIFIER = ShopgateOrder::PAYPAL;
    const XML_CONFIG_ENABLED = 'payment/paypal_standard/active';
    const PAYMENT_MODEL      = 'paypal/standard';

    /**
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($magentoOrder)
    {
        $info         = $this->getShopgateOrder()->getPaymentInfos();
        $magentoOrder = parent::manipulateOrderWithPaymentData($magentoOrder);
        $transaction  = $this->_createTransaction($magentoOrder);
        
        $magentoOrder->getPayment()->importTransactionInfo($transaction);
        $magentoOrder->getPayment()->setLastTransId($info['transaction_id']);
        return $magentoOrder;
    }


    /**
     * Handles magento transaction creation
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return mixed
     */
    protected function _createTransaction($magentoOrder)
    {
        $info        = $this->getShopgateOrder()->getPaymentInfos();
        $transaction = Mage::getModel("sales/order_payment_transaction");
        try {
            $transaction->setOrderPaymentObject($magentoOrder->getPayment());
            if ($magentoOrder->getBaseTotalDue()) {
                $transaction->setIsClosed(false);
            } else {
                $transaction->setIsClosed(true);
            }
            $transaction->setTxnId($info['transaction_id']);
            if ($this->getShopgateOrder()->getIsPaid()) {
                $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
            } else {
                $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
            }
        } catch (Exception $x) {
            $this->log($x->getMessage());
        }
        return $transaction->save();
    }

    /**
     * Get status with gateway
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return Mage_Sales_Model_Order
     */
    public function setOrderStatus($magentoOrder)
    {
        $info = $this->getShopgateOrder()->getPaymentInfos();
        return $this->orderStatusManager($magentoOrder, $info['payment_status']);
    }
}