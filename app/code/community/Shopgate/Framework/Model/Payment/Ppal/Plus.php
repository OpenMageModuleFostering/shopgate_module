<?php
/**
 * Class to manipulate the order payment data with amazon payment data
 *
 * @package      Shopgate_Framework
 * @author       Shopgate GmbH Butzbach
 */
class Shopgate_Framework_Model_Payment_Ppal_Plus
    extends Shopgate_Framework_Model_Payment_Pp_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const PAYMENT_IDENTIFIER = ShopgateOrder::PPAL_PLUS;
    const MODULE_CONFIG      = 'Iways_PayPalPlus';
    const PAYMENT_MODEL      = 'iways_paypalplus/payment';
    const XML_CONFIG_ENABLED = 'payment/iways_paypalplus_payment/active';

    /**
     * Create new order for PayPal Plus
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
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order)
    {
        $paymentInfos  = $this->getShopgateOrder()->getPaymentInfos();

        $trans = Mage::getModel('sales/order_payment_transaction');
        $trans->setOrderPaymentObject($order->getPayment());
        $trans->setIsClosed(false);
        $trans->setTxnId($paymentInfos['payment_id']);

        try {
            $invoice = $this->_getPaymentHelper()->createOrderInvoice($order);
            switch ($paymentInfos['status']) {
                case $this->_getPaymentHelper()->getPaypalCompletedStatus():
                    $trans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                    $order->getPayment()->setAmountAuthorized($order->getTotalDue());
                    $trans->setIsClosed(true);
                    if ($order->getPayment()->getIsTransactionPending()) {
                        $invoice->setIsPaid(false);
                    } else { // normal online capture: invoice is marked as "paid"
                        $invoice->setIsPaid(true);
                        $invoice->pay();
                    }
                    break;
                case $this->_getPaymentHelper()->getPaypalRefundedStatus():
                    break;
                case $this->_getPaymentHelper()->getPaypalPendingStatus():

                    $trans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                    $invoice->setIsPaid(false);
                    $order->getPayment()->setAmountAuthorized($order->getTotalDue());
                    break;
                default:
                    throw new Exception("Cannot handle payment status '{$paymentInfos['status']}'.");
            }
            $trans->save();
            $invoice->setTransactionId($paymentInfos['payment_transaction_id']);
            $this->_getPaymentHelper()->importPaymentInformation($order->getPayment(), $paymentInfos);
            $invoice->save();
            $order->addRelatedObject($invoice);
            $order->getPayment()->setLastTransId($paymentInfos['payment_id']);
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
        $quote->getPayment()->setTransactionId($data['payment_transaction_id']);
        $quote->getPayment()->setStatus($data['status']);
        return $quote;
    }

    /**
     * Set order status
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return Mage_Sales_Model_Order
     */
    public function setOrderStatus($magentoOrder)
    {
        return $this->orderStatusManager($magentoOrder);
    }

}
