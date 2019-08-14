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
 * Class Shopgate_Framework_Model_Payment_Authorize
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Cc_Authncim extends Shopgate_Framework_Model_Payment_Cc_AuthnAbstract
{
    const XML_CONFIG_ENABLED = 'payment/authnetcim/active';
    const MODULE_CONFIG      = 'ParadoxLabs_AuthorizeNetCim';

    /**
     * Order initialization values
     */
    protected $_orderInitialized = false;
    protected $_transactionType = '';
    protected $_responseCode = '';

    /**
     * Initialize public method
     *
     * @param $order Mage_Sales_Model_Order
     * @return $this
     */
    protected function _initOrder($order)
    {
        if ($this->_orderInitialized) {
            return $this;
        }
        $this->setOrder($order);
        $customer                = $this->_getCustomer();
        $paymentInfos            = $this->getShopgateOrder()->getPaymentInfos();
        $this->_transactionType  = $paymentInfos['transaction_type'];
        $this->_responseCode     = $paymentInfos['response_code'];
        $this->_orderInitialized = true;
        $this->_order->getPayment()->setCustomer($customer);
        return $this;
    }

    /**
     * @param $order            Mage_Sales_Model_Order
     *
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order)
    {
        $this->_initOrder($order);
        $paymentInfos = $this->getShopgateOrder()->getPaymentInfos();
        $lastFour     = substr($paymentInfos['credit_card']['masked_number'], -4);

        $this->_order->getPayment()->setCcTransId($paymentInfos['transaction_id']);
        $this->_order->getPayment()->setCcApproval($paymentInfos['authorization_number']);
        $this->_order->getPayment()->setLastTransId($paymentInfos['transaction_id']);
        $this->_order->getPayment()->setCcType($this->_getCcTypeName($paymentInfos['credit_card']['type']));
        $this->_order->getPayment()->setCcLast4($lastFour);
        $this->_order->getPayment()->setAdditionalInformation('transaction_id', $paymentInfos['transaction_id']);
        $authorizeCim = $this->_handleCard();
        $this->_order->getPayment()->setMethod($authorizeCim->getCode());
        $this->_order->getPayment()->setMethodInstance($authorizeCim);

        switch ($this->_transactionType) {
            case self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE:
                $newTransactionType      = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                $defaultExceptionMessage = Mage::helper('paygate')->__('Payment capturing error.');
                break;
            case self::SHOPGATE_PAYMENT_STATUS_AUTH_ONLY:
            default:
                $newTransactionType      = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                $defaultExceptionMessage = Mage::helper('paygate')->__('Payment authorization error.');
                break;
        }

        try {
            switch ($this->_responseCode) {
                case self::RESPONSE_CODE_APPROVED:
                    $this->_order->getPayment()->setAmountAuthorized($this->_order->getGrandTotal());
                    $this->_order->getPayment()->setBaseAmountAuthorized($this->_order->getBaseGrandTotal());
                    $this->_order->getPayment()->setIsTransactionPending(true);
                    $this->_createTransaction($newTransactionType);

                    if ($this->_transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
                        $this->_order->getPayment()->setIsTransactionPending(false);
                    }
                    break;
                case self::RESPONSE_CODE_HELD:
                    if ($this->_isOrderPendingReview()) {
                        $this->_createTransaction($newTransactionType, array('is_transaction_fraud' => true));
                        $this->_order->getPayment()->setIsTransactionPending(true)->setIsFraudDetected(true);
                    }
                    break;
                case self::RESPONSE_CODE_DECLINED:
                case self::RESPONSE_CODE_ERROR:
                    Mage::throwException($paymentInfos['response_reason_text']);
                    break;
                default:
                    Mage::throwException($defaultExceptionMessage);
            }
        } catch (Exception $x) {
            $this->_order->addStatusHistoryComment(Mage::helper('sales')->__('Note: %s', $x->getMessage()));
            Mage::logException($x);
        }

        $this->_createInvoice();
        $this->_order->save();
        return $this->_order;
    }

    /**
     * Creates transaction for order
     *
     * @param $type
     * @param $additionalInformation
     */
    protected function _createTransaction($type, $additionalInformation = array())
    {
        $orderPayment = $this->_order->getPayment();
        $transaction  = Mage::getModel('sales/order_payment_transaction');
        $transaction->setOrderPaymentObject($orderPayment);
        $transaction->setTxnId($orderPayment->getCcTransId());
        $transaction->setIsClosed(false);
        $transaction->setTxnType($type);
        $transaction->setData('is_transaciton_closed', '0');
        $transaction->setAdditionalInformation('real_transaction_id', $orderPayment->getCcTransId());
        foreach ($additionalInformation as $key => $value) {
            $transaction->setAdditionalInformation($key, $value);
        }
        $transaction->save();
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

    /**
     * Sets order status
     *
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order
     */
    public function setOrderStatus($order = null)
    {
        $this->_initOrder($order);
        $captured = $this->_order->getBaseCurrency()->formatTxt($this->_order->getBaseTotalInvoiced());
        $state    = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
        $status   = $this->_getHelper()->getStatusFromState($state);
        $message  = '';

        switch ($this->_responseCode) {
            case self::RESPONSE_CODE_APPROVED:
                $duePrice = $this->_order->getBaseCurrency()->formatTxt($this->_order->getTotalDue());
                $message  = Mage::helper('paypal')->__('Authorized amount of %s.', $duePrice);

                if ($this->_transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
                    $message = Mage::helper('sales')->__('Captured amount of %s online.', $captured);
                    $status  = Mage::getModel('authnetcim/method')->getConfigData('order_status');

                    if (!$status) {
                        $state  = Mage_Sales_Model_Order::STATE_PROCESSING;
                        $status = $this->_getHelper()->getStatusFromState($state);
                    }
                    $state = $this->_getHelper()->getStateForStatus($status);
                }
                break;
            case self::RESPONSE_CODE_HELD:
                if ($this->_isOrderPendingReview()) {
                    $message = Mage::helper('sales')->__(
                        'Capturing amount of %s is pending approval on gateway.',
                        $captured
                    );
                    $this->_order->setState($state, $status, $message);
                }
                break;
        }
        $this->_order->setState($state, $status, $message);
        $order->setShopgateStatusSet(true);
        return $order;
    }

    /**
     * Handles the creation of AuthnCIM card & profile
     *
     * @return ParadoxLabs_AuthorizeNetCim_Model_Method
     * @throws Exception
     */
    protected function _handleCard()
    {
        $shopgateOrder = $this->getShopgateOrder();
        $paymentInfos  = $shopgateOrder->getPaymentInfos();
        $authorizeCim  = Mage::getModel('authnetcim/method');
        $card          = $this->getCustomerCard();

        $card->setMethodInstance($authorizeCim);
        $card->importPaymentInfo($this->_order->getPayment());
        $card->setAdditional('request_amount', $shopgateOrder->getAmountComplete());
        $card->setAdditional('last_transaction_id', $paymentInfos['transaction_id']);
        $card->setAdditional('processed_amount', $shopgateOrder->getAmountComplete());
        $card->setAddress($this->_order->getBillingAddress());

        if ($this->_transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
            if ($this->_responseCode === self::RESPONSE_CODE_APPROVED || $this->_responseCode === self::RESPONSE_CODE_HELD) {
                $card->setAdditional('captured_amount', $card->getAdditional('processed_amount'));
            }
        }
        $card->save();
        $authorizeCim->setCard($card);
        return $authorizeCim;
    }

    /**
     * Handles adding the correct customer to the order.
     * Required for AuthnCIM creation. Call before _handleCard().
     *
     * @return Mage_Customer_Model_Customer
     * @throws Mage_Core_Exception
     */
    protected function _getCustomer()
    {
        $shopgateOrder = $this->getShopgateOrder();
        $customer      = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->loadByEmail($shopgateOrder->getMail());
        if (!$customer->getId()) {
            //no save, card will do that for us
            $customer
                ->setStore(Mage::app()->getStore())
                ->setFirstname($shopgateOrder->getInvoiceAddress()->getFirstName())
                ->setLastname($shopgateOrder->getInvoiceAddress()->getLastName())
                ->setEmail($shopgateOrder->getMail())
                ->setPassword(Mage::helper('core')->getRandomString(9));
        }
        return $customer;
    }

    /**
     * Checks if the order response is pending review
     *
     * @return bool
     */
    private function _isOrderPendingReview()
    {
        $paymentInfos = $this->getShopgateOrder()->getPaymentInfos();
        return array_key_exists('response_reason_code', $paymentInfos) && (
            $paymentInfos['response_reason_code'] == self::RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED
            || $paymentInfos['response_reason_code'] == self::RESPONSE_REASON_CODE_PENDING_REVIEW
        );
    }

    /**
     * Needs to be the right customer, active and last four match
     *
     * @param $lastFour - last four digits of the credit card
     * @return ParadoxLabs_TokenBase_Model_Card
     */
    private function _getCardByLastFour($lastFour)
    {
        $card = Mage::getModel('tokenbase/card')->getCollection();
        $card->addFieldToFilter('active', array('eq' => 1));
        $card->addFieldToFilter('customer_id', array('eq' => $this->_getCustomer()->getId()));
        $card->addFieldToFilter('additional', array('like' => '%s:8:"cc_last4";s:4:"' . $lastFour . '";%'));
        return $card->getFirstItem();
    }

    /**
     * Use this method to check if we can get a $card object
     * Then use regular Authorize when we cannot
     *
     * @return bool
     */
    public function checkGenericValid()
    {
        $card = $this->getCustomerCard();
        if (!$card
            || ($card instanceof Varien_Object
                && !$card->getId())
        ) {
            return false;
        }
        return true;
    }

    /**
     * Pulls a card from the database using last 4 digits
     * or creates on from transaction_id. On most errors,
     * returns false.
     *
     * @return false|ParadoxLabs_TokenBase_Model_Card
     */
    public function getCustomerCard()
    {
        $paymentInfos = $this->getShopgateOrder()->getPaymentInfos();
        $lastFour     = substr($paymentInfos['credit_card']['masked_number'], -4);
        $card         = $this->_getCardByLastFour($lastFour);

        if (!$card->getData()) {
            /** @var ParadoxLabs_TokenBase_Model_Card $card */
            $card = Mage::getModel('authnetcim/card'); //for actual authentication
            $card->setAdditional('cc_owner', $paymentInfos['credit_card']['holder']);
            $card->setAdditional('customer_email', $this->getShopgateOrder()->getMail());
            $card->setAdditional('cc_number', $lastFour);
            $card->setAdditional('cc_last4', $lastFour);
            $card->setMethod('authnetcim');
            $card->setCustomer($this->_getCustomer());

            /** @var Shopgate_Framework_Model_Payment_Cc_Authncim_Gateway $gateway */
            $gateway = Mage::getModel('shopgate/payment_cc_authncim_method')->gateway();
            try {
                $response = $gateway->setParameter('transId', $paymentInfos['transaction_id'])
                                    ->setParameter('cardNumber', 'XXXX' . $lastFour)
                                    ->createCustomerProfileFromTransactionRequest();
            } catch (Exception $e) {
                ShopgateLogger::getInstance()->log($e->getMessage(), ShopgateLogger::LOGTYPE_DEBUG);
                return false;
            }

            if (!isset($response['customerPaymentProfileId'])) {
                $error = $this->_getHelper()->__('Could not retrieve AuthorizeCIM ProfileID from response');
                ShopgateLogger::getInstance()->log($error, ShopgateLogger::LOGTYPE_DEBUG);
                return false;
            }
            $card->setProfileId($response['customerProfileId']);
            $card->setPaymentId($response['customerPaymentProfileId']);
            $card->save();
        }

        return $card;
    }
}