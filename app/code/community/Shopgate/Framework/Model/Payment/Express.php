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
 * User: pliebig
 * Date: 10.09.14
 * Time: 10:05
 * E-Mail: p.liebig@me.com, peter.liebig@magcorp.de
 */

/**
 * class to manipulate the order payment data with express data if wspp is active
 *
 * @package     Shopgate_Framework_Model_Payment_Express
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */
class Shopgate_Framework_Model_Payment_Express
{
    /**
     * create new order for paypal express (type wspp)
     *
     * @param $quote            Mage_Sales_Model_Quote
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public function createNewOrder($quote)
    {
        $convert     = Mage::getModel('sales/convert_quote');
        $transaction = Mage::getModel('core/resource_transaction');

        if ($quote->getCustomerId()) {
            $transaction->addObject($quote->getCustomer());
        }
        $quote->setTotalsCollectedFlag(true);
        $transaction->addObject($quote);
        if ($quote->isVirtual()) {
            $order = $convert->addressToOrder($quote->getBillingAddress());
        } else {
            $order = $convert->addressToOrder($quote->getShippingAddress());
        }
        $order->setBillingAddress($convert->addressToOrderAddress($quote->getBillingAddress()));
        if ($quote->getBillingAddress()->getCustomerAddress()) {
            $order->getBillingAddress()->setCustomerAddress($quote->getBillingAddress()->getCustomerAddress());
        }
        if (!$quote->isVirtual()) {
            $order->setShippingAddress($convert->addressToOrderAddress($quote->getShippingAddress()));
            if ($quote->getShippingAddress()->getCustomerAddress()) {
                $order->getShippingAddress()->setCustomerAddress($quote->getShippingAddress()->getCustomerAddress());
            }
        }

        $order->setPayment($convert->paymentToOrderPayment($quote->getPayment()));
        $order->getPayment()->setTransactionId($quote->getPayment()->getTransactionId());

        foreach ($quote->getAllItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $orderItem = $convert->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $order->addItem($orderItem);
        }
        $order->setQuote($quote);
        $order->setExtOrderId($quote->getPayment()->getTransactionId());
        $order->setCanSendNewEmailFlag(false);
        $transaction->addObject($order);
        $transaction->addCommitCallback(array($order, 'save'));

        try {
            $transaction->save();
            Mage::dispatchEvent(
                'sales_model_service_quote_submit_success',
                array(
                    'order' => $order,
                    'quote' => $quote
                )
            );
        } catch (Exception $e) {
            //reset order ID's on exception, because order not saved
            $order->setId(null);
            /** @var $item Mage_Sales_Model_Order_Item */
            foreach ($order->getItemsCollection() as $item) {
                $item->setOrderId(null);
                $item->setItemId(null);
            }

            Mage::dispatchEvent(
                'sales_model_service_quote_submit_failure',
                array(
                    'order' => $order,
                    'quote' => $quote
                )
            );
            throw $e;
        }
        Mage::dispatchEvent('checkout_submit_all_after', array('order' => $order, 'quote' => $quote));
        Mage::dispatchEvent('sales_model_service_quote_submit_after', array('order' => $order, 'quote' => $quote));

        return $order;
    }

    /**
     * @param $order            Mage_Sales_Model_Order
     * @param $shopgateOrder    ShopgateOrder
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order, $shopgateOrder)
    {
        $paymentInfos  = $shopgateOrder->getPaymentInfos();
        $paymentStatus = $this->_getPaymentHelper()->filterPaymentStatus($paymentInfos['payment_status']);
        $trans = Mage::getModel('sales/order_payment_transaction');
        $trans->setOrderPaymentObject($order->getPayment());
        $trans->setTxnId($paymentInfos['transaction_id']);
        $trans->setIsClosed(false);
        
        try {
            switch ($paymentStatus) {
                // paid
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED:
                    $invoice = $this->_getPaymentHelper()->createOrderInvoice($order);
                    $trans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                    
                    if ($order->getPayment()->getIsTransactionPending()) {
                        $invoice->setIsPaid(false);
                    } else { // normal online capture: invoice is marked as "paid"
                        $invoice->setIsPaid(true);
                        $invoice->pay();
                    }
                    $invoice->setTransactionId($paymentInfos['transaction_id']);
                    $invoice->save();
                    $order->addRelatedObject($invoice);
                    break;
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING:
                    if(isset($paymentInfos['reason_code'])) {
                        $order->getPayment()->setIsTransactionPending(true);
                        $order->getPayment()->setIsFraudDetected(true);
                    }
                    $trans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                    $order->getPayment()->setAmountAuthorized($order->getTotalDue());
                    break;
                default:
                    throw new Exception("Cannot handle payment status '{$paymentStatus}'.");
            }
            $trans->save();
            $this->_getPaymentHelper()->importPaymentInformation($order->getPayment(), $paymentInfos);
            $order->getPayment()->setTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $paymentInfos
            );
            
            $order->getPayment()->setLastTransId($paymentInfos['transaction_id']);
            $this->_getPaymentHelper()->orderStatusManager($order, $paymentStatus);
            
        } catch (Exception $x) {
            $comment = $this->_getPaymentHelper()->createIpnComment(
                            $order,
                            Mage::helper('paypal')->__('Note: %s', $x->getMessage()),
                            true
            );
            $comment->save();
            Mage::logException($x);
        }
        return $order;
    }

    /**
     * @param $quote            Mage_Sales_Model_Quote
     * @param $data             array
     * @return Mage_Sales_Model_Quote
     */
    public function prepareQuote($quote, $data)
    {
        $this->_getPaymentHelper()->importToPayment(
             $data,
             $quote->getPayment()->getMethodInstance()->getInfoInstance()
        );
        $quote->getPayment()->setTransactionId($data['transaction_id']);
        $quote->getPayment()->setLastTransId($data['transaction_id']);
        $quote->getPayment()->setPaypalPayerId($data['payer_id']);
        $quote->getPayment()->setPaypalPayerStatus($data['payer_status']);
        $quote->getPayment()->setAdditionalInformation('paypal_express_checkout_payer_id', $data['transaction_id']);
        $quote->getPayment()->setAdditionalInformation('paypal_pending_reason', $data['pending_reason']);
        
        return $quote;
    }

    /**
     * @return Shopgate_Framework_Helper_Payment_Wspp
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('shopgate/payment_wspp');
    }
    
}