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
 * native implementation of authorize.net payment
 *
 * @package     Shopgate_Framework_Model_Payment_Authorize
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */
class Shopgate_Framework_Model_Payment_Authorize extends Shopgate_Framework_Model_Payment_Abstract
{
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

    const RESPONSE_REASON_CODE_APPROVED = 1;
    const RESPONSE_REASON_CODE_NOT_FOUND = 16;
    const RESPONSE_REASON_CODE_PARTIAL_APPROVE = 295;
    const RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED = 252;
    const RESPONSE_REASON_CODE_PENDING_REVIEW = 253;
    const RESPONSE_REASON_CODE_PENDING_REVIEW_DECLINED = 254;
    
    /**
     * @param $order            Mage_Sales_Model_Order
     * @param $shopgateOrder    ShopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order, $shopgateOrder)
    {
        $paymentInfos     = $shopgateOrder->getPaymentInfos();
        $paymentAuthorize = Mage::getModel('paygate/authorizenet');
        $order->getPayment()->setMethod($paymentAuthorize->getCode());
        $paymentAuthorize->setInfoInstance($order->getPayment());
        $order->getPayment()->setMethodInstance($paymentAuthorize);
        $order->save();

        $lastFour = substr($paymentInfos['credit_card']['masked_number'], -4);
        $order->getPayment()->setCcTransId($paymentInfos['transaction_id']);
        $order->getPayment()->setCcApproval($paymentInfos['authorization_code']);
        $cardStorage = $paymentAuthorize->getCardsStorage($order->getPayment());
        $card        = $cardStorage->registerCard();
        $card->setRequestedAmount($shopgateOrder->getAmountComplete())
             ->setBalanceOnCard("")
             ->setLastTransId($paymentInfos['transaction_id'])
             ->setProcessedAmount($shopgateOrder->getAmountComplete())
             ->setCcType($this->_getCcTypeName($paymentInfos['credit_card']['type']))
             ->setCcOwner($paymentInfos['credit_card']['holder'])
             ->setCcLast4($lastFour)
             ->setCcExpMonth("")
             ->setCcExpYear("")
             ->setCcSsIssue("")
             ->setCcSsStartMonth("")
             ->setCcSsStartYear("");

        $transactionType = $paymentInfos['transaction_type'];
        $responseCode   = $paymentInfos['response_code'];
        switch ($transactionType) {
            case self::SHOPGATE_PAYMENT_STATUS_AUTH_ONLY:
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                $defaultExceptionMessage = Mage::helper('paygate')->__('Payment authorization error.');
                break;
            case self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE:
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                $defaultExceptionMessage = Mage::helper('paygate')->__('Payment capturing error.');
                break;
        }
        
        try {
            switch ($responseCode) {
                case self::RESPONSE_CODE_APPROVED:
                    $formattedPrice = $order->getBaseCurrency()->formatTxt($order->getTotalDue());
                    $order->getPayment()->setAmountAuthorized($order->getGrandTotal());
                    $order->getPayment()->setBaseAmountAuthorized($order->getBaseGrandTotal());
                    $order->getPayment()->setIsTransactionPending(true);
                    $this->_createTransaction($order->getPayment(), $card, $newTransactionType);
                    $message = Mage::helper('paypal')->__('Authorized amount of %s.', $formattedPrice);
                    if ($transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
                        $invoice = $this->_getPaymentHelper()->createOrderInvoice($order);
                        $invoice->setTransactionId(1);
                        $order->getPayment()->setIsTransactionPending(false);
                        $amountToCapture = $order->getBaseCurrency()->formatTxt($invoice->getBaseGrandTotal());
                        $order->getPayment()->setBaseAmountPaidOnline($invoice->getBaseGrandTotal());
                        $card->setCapturedAmount($card->getProcessedAmount());
                        $message = Mage::helper('sales')->__('Captured amount of %s online.', $amountToCapture);
                        $invoice->setIsPaid(true);
                        $invoice->pay();
                        $invoice->save();
                        $order->addRelatedObject($invoice);
                    }
                    $cardStorage->updateCard($card);
                    
                    $status = $order->getPayment()->getMethodInstance()->getConfigData('order_status');
                    $state  = Mage::helper('shopgate')->getStateForStatus($status);
                    $order->setState($state, $status, $message);
                    break;
                case self::RESPONSE_CODE_HELD:
                    if (array_key_exists('response_reason_code', $paymentInfos) && (
                            $paymentInfos['response_reason_code'] == self::RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED
                            || $paymentInfos['response_reason_code'] == self::RESPONSE_REASON_CODE_PENDING_REVIEW
                        )
                    ) {
                        $this->_createTransaction($order->getPayment(), $card, $newTransactionType, array('is_transaction_fraud' => true));
                        $invoice         = $this->_getPaymentHelper()->createOrderInvoice($order);
                        $invoice->setTransactionId(1);
                        $invoice->setIsPaid(false);
                        $invoice->save();
                        $order->addRelatedObject($invoice);
                        $amountToCapture = $order->getBaseCurrency()->formatTxt($invoice->getBaseGrandTotal());
                        $message = Mage::helper('sales')->__('Capturing amount of %s is pending approval on gateway.', $amountToCapture);
                        if ($transactionType == self::SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE) {
                            $card->setCapturedAmount($card->getProcessedAmount());
                            $cardStorage->updateCard($card);
                        }
                        $order->getPayment()->setIsTransactionPending(true)->setIsFraudDetected(true);
                        $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true, $message);
                    }
                    break;
                case self::RESPONSE_CODE_DECLINED:
                case self::RESPONSE_CODE_ERROR:
                    Mage::throwException($paymentInfos['response_reason_text']);
                default:
                    Mage::throwException($defaultExceptionMessage);
            }
        } catch (Exception $x) {
            $order->addStatusHistoryComment(Mage::helper('sales')->__('Note: %s', $x->getMessage()));
            Mage::logException($x);
        }
        return $order;
    }
    
    /**
     * Retrieve credit card type by mapping
     *
     * @param  $ccType string
     * @return string
     */
    protected function _getCcTypeName($ccType)
    {
        switch ($ccType) {
            case 'visa':
                $ccType = 'VI';
                break;
            case 'mastercard':
                $ccType = 'MC';
                break;
            case 'american_express':
                $ccType = 'AE';
                break;
            case 'discover':
                $ccType = 'DI';
                break;
            case 'jcb':
                $ccType = 'JCB';
                break;
            case 'maestro':
                $ccType = 'SM';
                break;
            default:
                $ccType = 'OT';
                break;
        }
        return $ccType;
    }
    
    /**
     * @param $orderPayment
     * @param $card
     * @param $type
     * @param $additionalInformation
     */
    protected function _createTransaction($orderPayment, $card, $type, $additionalInformation = array())
    {
        $transaction = Mage::getModel('sales/order_payment_transaction');
        $transaction->setOrderPaymentObject($orderPayment);
        $transaction->setTxnId($card->getLastTransId());
        $transaction->setIsClosed(false);
        $transaction->setTxnType($type);
        $transaction->setData('is_transaciton_closed', '0');
        $transaction->setAdditionalInformation('real_transaction_id', $card->getLastTransId());
        foreach ($additionalInformation as $key =>$value) {
            $transaction->setAdditionalInformation($key, $value);
        }
        $transaction->save();
    }
}