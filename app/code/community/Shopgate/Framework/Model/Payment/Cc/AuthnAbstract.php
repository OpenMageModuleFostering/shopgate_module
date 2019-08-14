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
 * Class Shopgate_Framework_Model_Payment_Cc_AuthnAbstract
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Cc_AuthnAbstract extends Shopgate_Framework_Model_Payment_Cc_Abstract
{
    const PAYMENT_IDENTIFIER = ShopgateOrder::AUTHN_CC;

    /**
     * const for transaction types of shopgate
     */
    const SHOPGATE_PAYMENT_STATUS_AUTH_ONLY    = 'auth_only';
    const SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE = 'auth_capture';

    /**
     * const for response codes
     */
    const RESPONSE_CODE_APPROVED = 1;
    const RESPONSE_CODE_DECLINED = 2;
    const RESPONSE_CODE_ERROR    = 3;
    const RESPONSE_CODE_HELD     = 4;

    const RESPONSE_REASON_CODE_APPROVED                  = 1;
    const RESPONSE_REASON_CODE_NOT_FOUND                 = 16;
    const RESPONSE_REASON_CODE_PARTIAL_APPROVE           = 295;
    const RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED = 252;
    const RESPONSE_REASON_CODE_PENDING_REVIEW            = 253;
    const RESPONSE_REASON_CODE_PENDING_REVIEW_DECLINED   = 254;
    
    protected $_transactionType = '';
    protected $_responseCode    = '';
    
    /**
     * Checks if the order response is pending review
     *
     * @return bool
     */
    protected function _isOrderPendingReview()
    {
        $paymentInfos = $this->getShopgateOrder()->getPaymentInfos();
        
        return array_key_exists('response_reason_code', $paymentInfos)
        && (
            $paymentInfos['response_reason_code'] == self::RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED
            || $paymentInfos['response_reason_code'] == self::RESPONSE_REASON_CODE_PENDING_REVIEW
        );
    }
    
    /**
     * Sets order status
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     */
    public function setOrderStatus($order = null)
    {
        $captured = $this->_order->getBaseCurrency()->formatTxt($this->_order->getBaseTotalInvoiced());
        $state    = $this->_getHelper()->getStateForStatus('payment_review');
        $status   = $this->_getHelper()->getStatusFromState($state);
        $message  = '';
        
        switch ($this->_responseCode) {
            case self::RESPONSE_CODE_APPROVED:
                $duePrice = $this->_order->getBaseCurrency()->formatTxt($this->_order->getTotalDue());
                $message  = Mage::helper('paypal')->__('Authorized amount of %s.', $duePrice);
                
                if ($this->_transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
                    $message = Mage::helper('sales')->__('Captured amount of %s online.', $captured);
                    $state  = Mage_Sales_Model_Order::STATE_PROCESSING;
                    $status = $this->_getHelper()->getStatusFromState($state);
                }
                break;
            case self::RESPONSE_CODE_HELD:
                if ($this->_isOrderPendingReview()) {
                    $message = Mage::helper('sales')->__(
                        'Capturing amount of %s is pending approval on gateway.',
                        $captured
                    )
                    ;
                    $this->_order->setState($state, $status, $message);
                }
                break;
        }
        $this->_order->setState($state, $status, $message);
        $order->setShopgateStatusSet(true);
        
        return $order;
    }
    
    /**
     *  Handles invoice creation
     *
     * @return $this
     * @throws Exception
     */
    protected function _createInvoice()
    {
        $paymentInfos = $this->getShopgateOrder()->getPaymentInfos();
        
        switch ($this->_responseCode) {
            case self::RESPONSE_CODE_APPROVED:
                if ($this->_transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
                    $invoice = $this->_getPaymentHelper()->createOrderInvoice($this->_order);
                    $invoice->setTransactionId($paymentInfos['transaction_id']); //needed for refund
                    $this->_order->getPayment()->setBaseAmountPaidOnline($invoice->getBaseGrandTotal());
                    $invoice->setIsPaid(true);
                    $invoice->pay();
                    $invoice->save();
                    $this->_order->addRelatedObject($invoice);
                }
                break;
            case self::RESPONSE_CODE_HELD:
                if ($this->_isOrderPendingReview()) {
                    $invoice = $this->_getPaymentHelper()->createOrderInvoice($this->_order);
                    $invoice->setTransactionId($paymentInfos['transaction_id']);
                    $invoice->setIsPaid(false);
                    $invoice->save();
                    $this->_order->addRelatedObject($invoice);
                }
                break;
        }
        
        return $this;
    }
}