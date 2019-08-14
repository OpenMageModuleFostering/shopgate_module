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
 * Native implementation of Authorize.net
 *
 * @package Shopgate_Framework_Model_Payment_Cc_Authn
 * @author  Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 * @author  Konstantin Kiritenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Cc_Authn
    extends Shopgate_Framework_Model_Payment_Cc_AuthnAbstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const XML_CONFIG_ENABLED = 'payment/authorizenet/active';
    const MODULE_CONFIG      = 'Mage_Paygate';

    /**
     * Init variables
     */
    private function _initVariables()
    {
        $paymentInfos           = $this->getShopgateOrder()->getPaymentInfos();
        $this->_transactionType = $paymentInfos['transaction_type'];
        $this->_responseCode    = $paymentInfos['response_code'];
    }

    /**
     * Use AuthnCIM as guide to refactor this class
     *
     * @param $order            Mage_Sales_Model_Order
     *
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order)
    {
        $this->_initVariables();
        $shopgateOrder = $this->getShopgateOrder();
        $paymentInfos  = $shopgateOrder->getPaymentInfos();

        $this->_saveToCardStorage();
        $this->getOrder()->getPayment()->setCcTransId($paymentInfos['transaction_id']);
        $this->getOrder()->getPayment()->setCcApproval($paymentInfos['authorization_code']);
        $this->getOrder()->getPayment()->setLastTransId($paymentInfos['transaction_id']);

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
                    $this->getOrder()->getPayment()->setAmountAuthorized($this->getOrder()->getGrandTotal());
                    $this->getOrder()->getPayment()->setBaseAmountAuthorized($this->getOrder()->getBaseGrandTotal());
                    $this->getOrder()->getPayment()->setIsTransactionPending(true);
                    $this->_createTransaction($newTransactionType);

                    if ($this->_transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
                        $this->getOrder()->getPayment()->setIsTransactionPending(false);
                    }
                    break;
                case self::RESPONSE_CODE_HELD:
                    if ($this->_isOrderPendingReview()) {
                        $this->_createTransaction($newTransactionType, array('is_transaction_fraud' => true));
                        $this->getOrder()->getPayment()->setIsTransactionPending(true)->setIsFraudDetected(true);
                    }
                    break;
                case self::RESPONSE_CODE_DECLINED:
                case self::RESPONSE_CODE_ERROR:
                    Mage::throwException($paymentInfos['response_reason_text']);
                default:
                    Mage::throwException($defaultExceptionMessage);
            }
        } catch (Exception $x) {
            $this->getOrder()->addStatusHistoryComment(Mage::helper('sales')->__('Note: %s', $x->getMessage()));
            Mage::logException($x);
        }

        $this->_createInvoice();

        return $this->getOrder();
    }

    /**
     * @param Mage_Sales_Model_Order $magentoOrder
     *
     * @return Mage_Sales_Model_Order
     */
    public function setOrderStatus($magentoOrder)
    {
        $status = Mage::getModel('paygate/authorizenet')->getConfigData('order_status');
        if ($status) {
            $state = $this->_getHelper()->getStateForStatus($status);
            $magentoOrder->setShopgateStatusSet(true);
            return $magentoOrder->setState($state, $status);
        } else {
            return parent::setOrderStatus($magentoOrder);
        }
    }

    /**
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
     * Utilize card storage if it exists
     * It does not in mage 1.4.0.0
     *
     * @throws Exception
     */
    protected function _saveToCardStorage()
    {
        $paymentAuthorize = Mage::getModel('paygate/authorizenet');

        $this->getOrder()->getPayment()->setMethod($paymentAuthorize->getCode());
        $paymentAuthorize->setInfoInstance($this->getOrder()->getPayment());
        $this->getOrder()->getPayment()->setMethodInstance($paymentAuthorize);
        $this->getOrder()->save();

        if (!method_exists($paymentAuthorize, 'getCardsStorage')) {
            return $this;
        }

        $paymentInfos = $this->getShopgateOrder()->getPaymentInfos();
        $lastFour     = substr($paymentInfos['credit_card']['masked_number'], -4);
        $cardStorage  = $paymentAuthorize->getCardsStorage($this->getOrder()->getPayment());
        $card         = $cardStorage->registerCard();
        $card->setRequestedAmount($this->getShopgateOrder()->getAmountComplete())
             ->setBalanceOnCard("")
             ->setLastTransId($paymentInfos['transaction_id'])
             ->setProcessedAmount($this->getShopgateOrder()->getAmountComplete())
             ->setCcType($this->_getCcTypeName($paymentInfos['credit_card']['type']))
             ->setCcOwner($paymentInfos['credit_card']['holder'])
             ->setCcLast4($lastFour)
             ->setCcExpMonth($paymentInfos['credit_card']['expiry_month'])
             ->setCcExpYear($paymentInfos['credit_card']['expiry_year'])
             ->setCcSsIssue("")
             ->setCcSsStartMonth("")
             ->setCcSsStartYear("");

        switch ($this->_responseCode) {
            case self::RESPONSE_CODE_APPROVED:
                if ($this->_transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
                    $card->setCapturedAmount($card->getProcessedAmount());
                }
                $cardStorage->updateCard($card);
                break;
            case self::RESPONSE_CODE_HELD:
                if ($this->_isOrderPendingReview()) {
                    if ($this->_transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
                        $card->setCapturedAmount($card->getProcessedAmount());
                    }
                    $cardStorage->updateCard($card);
                }
                break;
        }
        return $this;
    }
}