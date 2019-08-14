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
 * native model for usa epay
 *
 * @package     Shopgate_Framework_Model_Payment_Usaepay
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */
class Shopgate_Framework_Model_Payment_Usaepay extends Shopgate_Framework_Model_Payment_Abstract
{

    /**
     * @param $order            Mage_Sales_Model_Order
     * @param $shopgateOrder    ShopgateOrder
     *
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order, $shopgateOrder)
    {
        $paymentInfos   = $shopgateOrder->getPaymentInfos();
        // changing order payment method here cause otherwise validation fails cause not CC number, no expiration date
        $paymentUsaepay = Mage::getModel('usaepay/CCPaymentAction');
        $order->getPayment()->setMethod($paymentUsaepay->getCode());
        $paymentUsaepay->setInfoInstance($order->getPayment());
        $order->getPayment()->setMethodInstance($paymentUsaepay);
        $order->save();
        
        $lastFour = substr($paymentInfos['credit_card']['masked_number'], -4);
        $order->getPayment()->setCcNumberEnc($paymentInfos['credit_card']['masked_number']);
        $order->getPayment()->setCCLast4($lastFour);
        $order->getPayment()->setCcTransId($paymentInfos['reference_number']);
        $order->getPayment()->setCcApproval($paymentInfos['authorization_number']);
        $order->getPayment()->setCcType($this->_getCcTypeName($paymentInfos['credit_card']['type']));
        $order->getPayment()->setCcOwner($paymentInfos['credit_card']['holder']);
        $order->getPayment()->setLastTransId($paymentInfos['reference_number']);
        
        // C or A type. no const in usa epay model for this
        $paymentStatus = $shopgateOrder->getIsPaid() ? 'C' : 'A';
        try {
            $status  = true;
            $invoice = $this->_getPaymentHelper()->createOrderInvoice($order);
            switch ($paymentStatus) {
                case 'C':
                    $amountToCapture = $order->getBaseCurrency()->formatTxt($invoice->getBaseGrandTotal());
                    $order->getPayment()->setAmountAuthorized($invoice->getGrandTotal());
                    $order->getPayment()->setBaseAmountAuthorized($invoice->getBaseGrandTotal());
                    $order->getPayment()->setBaseAmountPaidOnline($invoice->getBaseGrandTotal());
                    $message = Mage::helper('sales')->__('Captured amount of %s online.', $amountToCapture);
                    $state   = Mage_Sales_Model_Order::STATE_PROCESSING;
                    $invoice->setIsPaid(true);
                    $invoice->setTransactionId($paymentInfos['reference_number']);
                    $invoice->pay();
                    $invoice->save();
                    $order->addRelatedObject($invoice);
                    $order->setState($state, $status, $message);
                    break;
                case 'A':
                    $formattedPrice = $order->getBaseCurrency()->formatTxt($order->getTotalDue());
                    $order->getPayment()->setAmountAuthorized($order->getGrandTotal());
                    $order->getPayment()->setBaseAmountAuthorized($order->getBaseGrandTotal());
                    $order->getPayment()->setIsTransactionPending(true);
                    $message = Mage::helper('paypal')->__('Authorized amount of %s.', $formattedPrice);
                    $state   = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
                    $invoice->setIsPaid(false);
                    $invoice->save();
                    $order->addRelatedObject($invoice);
                    $order->setState($state, $status, $message);
                    break;
                default:
                    throw new Exception("Cannot handle payment status '{$paymentStatus}'.");
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
}