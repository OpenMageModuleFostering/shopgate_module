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
 * Class Shopgate_Framework_Model_Payment_Pp_Abstract
 *
 * @author  Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Pp_Abstract
    extends Shopgate_Framework_Model_Payment_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const PAYMENT_IDENTIFIER = 'PP';
    const MODULE_CONFIG      = 'Mage_Paypal';

    /**
     * History message action map
     *
     * @var array
     */
    protected $_messageStatusAction = array(
        Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED => 'Captur',
        Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING   => 'Authoriz'
    );

    /**
     * Depends on Shopgate paymentInfos() to be passed
     * into the TransactionAdditionalInfo of $order.
     *
     * @param $paymentStatus String
     * @param $order         Mage_Sales_Model_Order
     * @return Mage_Sales_Model_Order
     */
    public function orderStatusManager(Mage_Sales_Model_Order $order, $paymentStatus = null)
    {
        if (!$paymentStatus) {
            $rawData       = $order->getPayment()->getTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
            );
            $paymentStatus = $rawData['payment_status'];
        }

        $total  = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
        $state  = Mage_Sales_Model_Order::STATE_PROCESSING;
        $action = $this->getActionByStatus(strtolower($paymentStatus));

        if ($order->getPayment()->getIsTransactionPending()) {
            $message = Mage::helper('paypal')->__(
                '%sing amount of %s is pending approval on gateway.',
                $action,
                $total
            );
            $state   = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
        } else {
            $message = Mage::helper('paypal')->__(
                '%sed amount of %s online.',
                $action,
                $total
            );
        }

        //test for fraud
        if ($order->getPayment()->getIsFraudDetected()) {
            $status = Mage_Sales_Model_Order::STATUS_FRAUD;
            $state  = Mage::helper('shopgate')->getStateForStatus($status);
            if (!$state) {
                $state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            }
        }

        if (!isset($status)) {
            $status = Mage::helper('shopgate')->getStatusFromState($state);
        }
        $order->setState($state, $status, $message);
        $order->setShopgateStatusSet(true);
        return $order;
    }

    /**
     * Maps correct message action based on order status.
     * E.g. authorize if pending, capture on complete
     *
     * @param $paymentStatus
     * @return string
     */
    public function getActionByStatus($paymentStatus)
    {
        return isset($this->_messageStatusAction[$paymentStatus]) ?
            $this->_messageStatusAction[$paymentStatus] : 'Authoriz';
    }
}