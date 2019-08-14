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
 * Native model for usa epay
 *
 * @package Shopgate_Framework_Model_Payment_Usaepay
 * @author  Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 * @author  Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Cc_Usaepay extends Shopgate_Framework_Model_Payment_Cc_Abstract
{
    const PAYMENT_IDENTIFIER     = ShopgateOrder::USAEPAY_CC;
    const XML_CONFIG_ENABLED     = 'payment/usaepay/active';
    const XML_CONFIG_STATUS_PAID = 'payment/usaepay/order_status';
    const MODULE_CONFIG          = 'Mage_Usaepay';

    /**
     * @param $order            Mage_Sales_Model_Order
     *
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order)
    {
        $paymentInfos = $this->getShopgateOrder()->getPaymentInfos();
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
        $paymentStatus = $this->getShopgateOrder()->getIsPaid() ? 'C' : 'A';
        try {
            $invoice = $this->_getPaymentHelper()->createOrderInvoice($order);
            switch ($paymentStatus) {
                case 'C':
                    $order->getPayment()->setAmountAuthorized($invoice->getGrandTotal());
                    $order->getPayment()->setBaseAmountAuthorized($invoice->getBaseGrandTotal());
                    $order->getPayment()->setBaseAmountPaidOnline($invoice->getBaseGrandTotal());
                    $invoice->setIsPaid(true);
                    $invoice->setTransactionId($paymentInfos['reference_number']);
                    $invoice->pay();
                    $invoice->save();
                    $order->addRelatedObject($invoice);
                    break;
                case 'A':
                    $order->getPayment()->setAmountAuthorized($order->getGrandTotal());
                    $order->getPayment()->setBaseAmountAuthorized($order->getBaseGrandTotal());
                    $order->getPayment()->setIsTransactionPending(true);
                    $invoice->setIsPaid(false);
                    $invoice->save();
                    $order->addRelatedObject($invoice);
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
     * A bit dirty, but status setting can be complicated
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return Mage_Sales_Model_Order
     */
    public function setOrderStatus($magentoOrder)
    {
        if ($this->getShopgateOrder()->getIsPaid()) {
            $total  = $magentoOrder->getBaseCurrency()->formatTxt($magentoOrder->getBaseGrandTotal());
            $status = Mage::getStoreConfig(self::XML_CONFIG_STATUS_PAID, $magentoOrder->getStoreId());
            if (!$status) {
                $state  = Mage_Sales_Model_Order::STATE_PROCESSING;
                $status = Mage::helper('shopgate')->getStatusFromState($state);
            } else {
                $state = Mage::helper('shopgate')->getStateForStatus($status);
            }
            $message = Mage::helper('sales')->__('Captured amount of %s online.', $total);
        } else {
            $state   = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            $status  = Mage::helper('shopgate')->getStatusFromState($state);
            $due     = $magentoOrder->getBaseCurrency()->formatTxt($magentoOrder->getTotalDue());
            $message = Mage::helper('paypal')->__('Authorized amount of %s.', $due);
        }

        $magentoOrder->setState($state, $status, $message);
        $magentoOrder->setShopgateStatusSet(true);

        return $magentoOrder;
    }
}