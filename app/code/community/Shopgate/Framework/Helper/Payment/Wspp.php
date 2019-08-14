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
 * special helper for website payments pro
 *
 * @package     Shopgate_Framework_Helper_Payment_Wspp
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */
class Shopgate_Framework_Helper_Payment_Wspp extends Shopgate_Framework_Helper_Payment_Abstract
{
    // Next two fields are required for Brazil
    const BUYER_TAX_ID          = 'buyer_tax_id';
    const BUYER_TAX_ID_TYPE     = 'buyer_tax_id_type';
    const PAYMENT_STATUS        = 'payment_status';
    const PENDING_REASON        = 'pending_reason';
    const IS_FRAUD              = 'is_fraud_detected';
    const PAYMENT_STATUS_GLOBAL = 'paypal_payment_status';
    const PENDING_REASON_GLOBAL = 'paypal_pending_reason';
    const IS_FRAUD_GLOBAL       = 'paypal_is_fraud_detected';

    /**
     * All payment information map
     *
     * @var array
     */
    protected $_paymentMap = array(
        Mage_Paypal_Model_Info::PAYER_ID       => 'paypal_payer_id',
        Mage_Paypal_Model_Info::PAYER_EMAIL    => 'paypal_payer_email',
        Mage_Paypal_Model_Info::PAYER_STATUS   => 'paypal_payer_status',
        Mage_Paypal_Model_Info::ADDRESS_ID     => 'paypal_address_id',
        Mage_Paypal_Model_Info::ADDRESS_STATUS => 'paypal_address_status',
        Mage_Paypal_Model_Info::PROTECTION_EL  => 'paypal_protection_eligibility',
        Mage_Paypal_Model_Info::FRAUD_FILTERS  => 'paypal_fraud_filters',
        Mage_Paypal_Model_Info::CORRELATION_ID => 'paypal_correlation_id',
        Mage_Paypal_Model_Info::AVS_CODE       => 'paypal_avs_code',
        Mage_Paypal_Model_Info::CVV2_MATCH     => 'paypal_cvv2_match',
        Mage_Paypal_Model_Info::CENTINEL_VPAS  => Mage_Paypal_Model_Info::CENTINEL_VPAS,
        Mage_Paypal_Model_Info::CENTINEL_ECI   => Mage_Paypal_Model_Info::CENTINEL_ECI,
        self::BUYER_TAX_ID                     => self::BUYER_TAX_ID,
        self::BUYER_TAX_ID_TYPE                => self::BUYER_TAX_ID_TYPE,
    );

    /**
     * System information map
     *
     * @var array
     */
    protected $_systemMap = array(
        self::PAYMENT_STATUS => self::PAYMENT_STATUS_GLOBAL,
        self::PENDING_REASON => self::PENDING_REASON_GLOBAL,
        self::IS_FRAUD       => self::IS_FRAUD_GLOBAL,
    );

    /**
     * PayPal payment status possible values not in 1.4 so copied here
     *
     * @var string
     */
    const PAYMENTSTATUS_NONE         = 'none';
    const PAYMENTSTATUS_COMPLETED    = 'completed';
    const PAYMENTSTATUS_DENIED       = 'denied';
    const PAYMENTSTATUS_EXPIRED      = 'expired';
    const PAYMENTSTATUS_FAILED       = 'failed';
    const PAYMENTSTATUS_INPROGRESS   = 'in_progress';
    const PAYMENTSTATUS_PENDING      = 'pending';
    const PAYMENTSTATUS_REFUNDED     = 'refunded';
    const PAYMENTSTATUS_REFUNDEDPART = 'partially_refunded';
    const PAYMENTSTATUS_REVERSED     = 'reversed';
    const PAYMENTSTATUS_UNREVERSED   = 'canceled_reversal';
    const PAYMENTSTATUS_PROCESSED    = 'processed';
    const PAYMENTSTATUS_VOIDED       = 'voided';

    /**
     * @var null
     */
    protected $_request = null;
    
    /**
     * History message action map
     * @var array
     */
    protected $_messageStatusAction = array(
        Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED => 'Captur',
        Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING   => 'Authoriz'
    );
    
    /**
     * IPN request data getter
     *
     * @param string $key
     * @return array|string
     */
    public function getRequestData($key = null)
    {
        if (null === $key) {
            return $this->_request;
        }
        return isset($this->_request[$key]) ? $this->_request[$key] : null;
    }

    /**
     * Filter payment status from NVP into paypal/info format
     *
     * @param string $ipnPaymentStatus
     * @return string
     */
    public function filterPaymentStatus($ipnPaymentStatus)
    {
        switch ($ipnPaymentStatus) {
            case 'Created': // break is intentionally omitted
            case 'Completed':
                return self::PAYMENTSTATUS_COMPLETED;
            case 'Denied':
                return self::PAYMENTSTATUS_DENIED;
            case 'Expired':
                return self::PAYMENTSTATUS_EXPIRED;
            case 'Failed':
                return self::PAYMENTSTATUS_FAILED;
            case 'Pending':
                return self::PAYMENTSTATUS_PENDING;
            case 'Refunded':
                return self::PAYMENTSTATUS_REFUNDED;
            case 'Reversed':
                return self::PAYMENTSTATUS_REVERSED;
            case 'Canceled_Reversal':
                return self::PAYMENTSTATUS_UNREVERSED;
            case 'Processed':
                return self::PAYMENTSTATUS_PROCESSED;
            case 'Voided':
                return self::PAYMENTSTATUS_VOIDED;
        }
        return '';
    }

    /**
     * Map payment information from IPN to payment object
     * Returns true if there were changes in information
     *
     * @param $payment
     * @param $paypalIpnData
     * @return bool
     */
    public function importPaymentInformation($payment, $paypalIpnData)
    {
        $this->_request      = $paypalIpnData;
        $was                 = $payment->getAdditionalInformation();
        $info                = Mage::getSingleton('paypal/info');
        $paymentInfoInstance = $payment->getMethodInstance()->getInfoInstance();
        $from                = array();
        foreach ($this->_getPaymentInfoKeys() as $privateKey => $publicKey) {
            if (is_int($privateKey)) {
                $privateKey = $publicKey;
            }
            $value = $this->getRequestData($privateKey);
            if ($value) {
                $from[$publicKey] = $value;
            }
        }
        if (isset($from['payment_status'])) {
            $from['payment_status'] = $this->filterPaymentStatus($this->getRequestData('payment_status'));
        }

        // collect fraud filters
        $fraudFilters = array();
        for ($i = 1; $value = $this->getRequestData("fraud_management_pending_filters_{$i}"); $i++) {
            $fraudFilters[] = $value;
        }
        if ($fraudFilters) {
            $from[Mage_Paypal_Model_Info::FRAUD_FILTERS] = $fraudFilters;
        }

        $info->importToPayment($from, $paymentInfoInstance);

        /**
         * Detect pending payment, frauds
         * TODO: implement logic in one place
         *
         * @see Mage_Paypal_Model_Pro::importPaymentInfo()
         */
        if ($info->isPaymentReviewRequired($paymentInfoInstance)) {
            $paymentInfoInstance->setIsTransactionPending(true);
            if ($fraudFilters) {
                $paymentInfoInstance->setIsFraudDetected(true);
            }
        }
        if ($info->isPaymentSuccessful($paymentInfoInstance)) {
            $paymentInfoInstance->setIsTransactionApproved(true);
        } elseif ($info->isPaymentFailed($paymentInfoInstance)) {
            $paymentInfoInstance->setIsTransactionDenied(true);
        }

        return $was != $paymentInfoInstance->getAdditionalInformation();
    }

    /**
     * Generate an "IPN" comment with additional explanation.
     * Returns the generated comment or order status history object
     *
     * @param Mage_Sales_Model_Order $order
     * @param string                 $comment
     * @param bool                   $addToHistory
     *
     * @return string|Mage_Sales_Model_Order_Status_History
     */
    public function createIpnComment($order, $comment = '', $addToHistory = false)
    {
        $paymentStatus = $this->getRequestData('payment_status');
        $message       = Mage::helper('paypal')->__('IPN "%s".', $paymentStatus);
        if ($comment) {
            $message .= ' ' . $comment;
        }
        if ($addToHistory) {
            $message = $order->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }
        return $message;
    }

    /**
     * Grab data from source and map it into payment
     *
     * @param array|Varien_Object|callback $from
     * @param Mage_Payment_Model_Info      $payment
     */
    public function importToPayment($from, Mage_Payment_Model_Info $payment)
    {
        $fullMap = array_merge($this->_paymentMap, $this->_systemMap);
        if (is_object($from)) {
            $from = array($from, 'getDataUsingMethod');
        }
        Varien_Object_Mapper::accumulateByMap($from, array($payment, 'setAdditionalInformation'), $fullMap);
    }

    /**
     * @return array
     */
    protected function _getPaymentInfoKeys()
    {
        return array(
            Mage_Paypal_Model_Info::PAYER_ID,
            'payer_email' => Mage_Paypal_Model_Info::PAYER_EMAIL,
            Mage_Paypal_Model_Info::PAYER_STATUS,
            Mage_Paypal_Model_Info::ADDRESS_STATUS,
            Mage_Paypal_Model_Info::PROTECTION_EL,
            self::PAYMENT_STATUS,
            self::PENDING_REASON,
            self::SHOPGATE_CC_STRING,
            self::SHOPGATE_CC_TYPE_STRING,
            self::SHOPGATE_CC_HOLDER_STRING
        );
    }
    
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
        if(!$paymentStatus){
            $rawData = $order->getPayment()->getTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
            );
            $paymentStatus = strtolower($rawData['payment_status']);
        }
        
        $formattedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
        $state          = $status = Mage_Sales_Model_Order::STATE_PROCESSING;
        $action         = $this->getActionByStatus($paymentStatus);
        
        if ($order->getPayment()->getIsTransactionPending()) {
            $message = Mage::helper('paypal')->__(
                '%sing amount of %s is pending approval on gateway.',
                $action,
                $formattedPrice
            );
            $state = $status = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
        } else {
            $message = Mage::helper('paypal')->__(
                '%sed amount of %s online.',
                $action,
                $formattedPrice
            );
        }
        //test for fraud
        if ($order->getPayment()->getIsFraudDetected()) {
            $state  = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            $status = Mage_Sales_Model_Order::STATUS_FRAUD;
        }
        
        return $order->setState($state, $status, $message);
    }
    
    /**
     * Maps correct message action based on order status.
     * E.g. authorize if pending, capture on complete
     * @param $paymentStatus
     * @return string
     */
    public function getActionByStatus($paymentStatus)
    {
        return isset($this->_messageStatusAction[$paymentStatus]) ? 
            $this->_messageStatusAction[$paymentStatus] : 'Authoriz';
    }
}